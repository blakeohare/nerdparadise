using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using AutoGraderHarness.Graders;

namespace AutoGraderHarness
{
	class RunnerProcess
	{
		private WorkQueue queue;
		private int label;
		public RunnerProcess(WorkQueue queue)
		{
			this.queue = queue;
		}

		public void Start(int label)
		{
			System.Threading.Thread thread = new System.Threading.Thread(
				new System.Threading.ParameterizedThreadStart(
					obj =>
					{
						this.Run();
					}
				)
			);
			thread.Start();
			System.Console.WriteLine("Thread #" + label + " is now polling.");
		}

		public void Run()
		{
			while (true)
			{
				string token = this.queue.PopWorkItem();
				if (token == null)
				{
					System.Threading.Thread.Sleep(100);
				}
				else
				{
					HttpRequest request = new HttpRequest("POST", "http://np10.nfshost.com/autograder/graderpoll/claim", new Dictionary<string, string>()
					{
						{ "key", Util.HashWithSecret(token) },
						{ "token", token },
					});
					ThreadSafeConsoleWriter.Print("[" + token + "] Claiming...");
					request.Send();

					if (request.ResponseCode == 200)
					{
						string[] data = request.ResponseBody.Trim().Split(',');
						if (data[0] == "OK" && data.Length == 10)
						{
							string language = Util.HexToString(data[1]);
							string code = Util.HexToString(data[2]);
							string callback = Util.HexToString(data[3]);
							string testFunctionName = Util.HexToString(data[4]);
							string expectedArgCount = Util.HexToString(data[5]);
							string[] argTypes = Util.HexToString(data[6]).Split('|');
							string returnType = Util.HexToString(data[7]);
							string testJson = Util.HexToString(data[8]);
							string feature = Util.HexToString(data[9]);

							List<object> testInput = null;
							List<object> expectedOutput = null;

							Dictionary<string, object> tests = JsonParser.ParseJsonIntoValue(testJson) as Dictionary<string, object>;
							if (tests != null && tests.ContainsKey("input") && tests.ContainsKey("output"))
							{
								testInput = tests["input"] as List<object>;
								expectedOutput = tests["output"] as List<object>;
							}

							ThreadSafeConsoleWriter.Print("[" + token + "] running " + language + " entry for " + feature + ".");
							switch (language)
							{
								case "crayon":
									this.RunCrayonCode(token, code, callback, testFunctionName, expectedArgCount, testInput, expectedOutput, returnType, argTypes, feature);
									break;
								case "python":
									this.RunPythonCode(token, code, callback, testFunctionName, expectedArgCount, testInput, expectedOutput, returnType, argTypes, feature);
									break;
								default:
									break;
							}
							ThreadSafeConsoleWriter.Print("[" + token + "] finished.");
						}
						else if (data[0] == "ERR" && data.Length > 1)
						{
							if (data[1] == "CONFLICT")
							{
								ThreadSafeConsoleWriter.Print("[" + token + "] already claimed!");
							}
							else
							{
								ThreadSafeConsoleWriter.Print("[" + token + "] unknown server response.");
							}
						}
						else
						{
							ThreadSafeConsoleWriter.Print("[" + token + "] unknown server response.");
						}
					}
				}
			}
		}

		private void RunPythonCode(string token, string code, string callback, string testFunctionName, string rawArgCountValue, List<object> testInputList, List<object> testOutputList, string returnType, string[] argTypes, string feature)
		{
			bool runTests = feature != "tinker";

			string secretStartToken = Util.GetGibberishString();
			string caseIteratorVar = Util.GetGibberishString();
			string inputListVar = Util.GetGibberishString();
			string outputListVar = Util.GetGibberishString();
			string succeedToken = Util.GetGibberishString();
			string failToken = Util.GetGibberishString();
			string functionDefinedCorrectlyToken = Util.GetGibberishString();
			string actualOutputVar = Util.GetGibberishString();
			string ranCorrectlyVar = Util.GetGibberishString();
			string answerCorrectVar = Util.GetGibberishString();
			string answerWrongVar = Util.GetGibberishString();
			string finishedToken = Util.GetGibberishString();
			string dummyVar = Util.GetGibberishString();

			PythonGrader grader = new PythonGrader(code);

			if (runTests)
			{
				int argCount;
				if (!int.TryParse(rawArgCountValue, out argCount))
				{
					this.ReportStatus(token, GraderState.ERROR_UNKNOWN);
					return;
				}

				string invocation = testFunctionName + "(";
				if (argCount == 0)
				{
					invocation += ")";
				}
				else if (argCount == 1)
				{
					invocation += inputListVar + "[" + caseIteratorVar + "])";
				}
				else
				{
					for (int i = 0; i < argCount; ++i)
					{
						if (i > 0) invocation += ", ";
						invocation += inputListVar + "[" + caseIteratorVar + "][" + i + "]";
					}
					invocation += ")";
				}

				code += string.Join("\n", new string[] {
					"\n",
					"print('" + secretStartToken + "')",
					dummyVar + " = " + testFunctionName,
					"print('" + functionDefinedCorrectlyToken + "')",
					inputListVar + " = " + grader.ConvertJsonToCode(testInputList),
					outputListVar + " = " + grader.ConvertJsonToCode(testOutputList),
					"\n",
					"for " + caseIteratorVar + " in range(len(" + inputListVar + ")):",
					
					"  " + actualOutputVar + " = " + invocation,
					"  print('" + ranCorrectlyVar + "');",
					"  if " + actualOutputVar + " == " + outputListVar + "[" + caseIteratorVar + "]:",
					"    print('" + answerCorrectVar + "')",
					"  else:",
					"    print('" + answerWrongVar + "')",
					"print('" + finishedToken + "')",
					"",
				});
			}

			grader = new PythonGrader(code);

			grader.SetUp();

			// TODO: refactor this. It's identical to the Crayon version.
			if (grader.State == GraderState.SETTING_UP)
			{
				grader.State = GraderState.RUNNING;
				this.ReportStatus(token, grader.State);
				string output = grader.Run();
				if (runTests)
				{
					int startToken = output.IndexOf(secretStartToken);
					if (startToken == -1)
					{
						this.ReportStatus(token, GraderState.ERROR_RUNTIME);
					}
					else
					{
						string gradesheet = output.Substring(startToken);
						if (!gradesheet.Contains(functionDefinedCorrectlyToken))
						{
							this.ReportStatus(token, GraderState.ERROR_REQUIRED_FUNCTION_NOT_DEFINED);
						}
						else if (!gradesheet.Contains(finishedToken))
						{
							this.ReportStatus(token, GraderState.ERROR_RUNTIME);
						}
						else
						{
							int runs = gradesheet.Split(new string[] { ranCorrectlyVar }, StringSplitOptions.None).Length - 1;
							int passes = gradesheet.Split(new string[] { answerCorrectVar }, StringSplitOptions.None).Length - 1;
							int fails = gradesheet.Split(new string[] { answerWrongVar }, StringSplitOptions.None).Length - 1;

							this.ReportConclusion(token, "SCORE," + runs + "," + passes + "," + fails, callback);
						}
					}
				}
				else
				{
					this.ReportConclusion(token, output, callback);
				}
			}
			else
			{
				this.ReportStatus(token, grader.State);
			}
		}

		private void RunCrayonCode(string token, string code, string callback, string testFunctionName, string rawArgCountValue, List<object> testInputList, List<object> testOutputList, string returnType, string[] argTypes, string feature)
		{
			bool runTests = feature != "tinker";

			string secretStartToken = Util.GetGibberishString();
			string caseIteratorVar = Util.GetGibberishString();
			string inputListVar = Util.GetGibberishString();
			string outputListVar = Util.GetGibberishString();
			string succeedToken = Util.GetGibberishString();
			string failToken = Util.GetGibberishString();
			string functionDefinedCorrectlyToken = Util.GetGibberishString();
			string actualOutputVar = Util.GetGibberishString();
			string ranCorrectlyVar = Util.GetGibberishString();
			string answerCorrectVar = Util.GetGibberishString();
			string answerWrongVar = Util.GetGibberishString();
			string finishedToken = Util.GetGibberishString();
			string dummyVar = Util.GetGibberishString();

			CrayonGrader grader = new CrayonGrader(code);

			if (runTests)
			{
				int argCount;
				if (!int.TryParse(rawArgCountValue, out argCount))
				{
					this.ReportStatus(token, GraderState.ERROR_UNKNOWN);
					return;
				}

				string invocation = testFunctionName + "(";
				if (argCount == 0)
				{
					invocation += ")";
				}
				else if (argCount == 1)
				{
					invocation += inputListVar + "[" + caseIteratorVar + "])";
				}
				else
				{
					for (int i = 0; i < argCount; ++i)
					{
						if (i > 0) invocation += ", ";
						invocation += inputListVar + "[" + caseIteratorVar + "][" + i + "]";
					}
					invocation += ")";
				}

				code += string.Join("\n", new string[] {
					"\n",
					"$print('" + secretStartToken + "');",
					dummyVar + " = " + testFunctionName + ";",
					"$print('" + functionDefinedCorrectlyToken + "');",
					inputListVar + " = " + grader.ConvertJsonToCode(testInputList) + ";",
					outputListVar + " = " + grader.ConvertJsonToCode(testOutputList) + ";",
					"\n",
					"for (" + caseIteratorVar + " = 0; " + caseIteratorVar + " < " + inputListVar + ".length; ++" + caseIteratorVar + ") {",
					"  " + actualOutputVar + " = " + invocation + ";",
					"  $print('" + ranCorrectlyVar + "');",
					"  if (" + actualOutputVar + " == " + outputListVar + "[" + caseIteratorVar + "]) {",
					"    $print('" + answerCorrectVar + "');",
					"  } else {",
					"    $print('" + answerWrongVar + "');",
					"  }",
					"}",
					"$print('" + finishedToken + "');",
					"",
				});
			}

			grader = new CrayonGrader(code);

			grader.SetUp();

			if (grader.State == GraderState.SETTING_UP)
			{
				grader.State = GraderState.RUNNING;
				this.ReportStatus(token, grader.State);
				string output = grader.Run();
				if (runTests)
				{
					int startToken = output.IndexOf(secretStartToken);
					if (startToken == -1)
					{
						this.ReportStatus(token, GraderState.ERROR_RUNTIME);
					}
					else
					{
						string gradesheet = output.Substring(startToken);
						if (!gradesheet.Contains(functionDefinedCorrectlyToken))
						{
							this.ReportStatus(token, GraderState.ERROR_REQUIRED_FUNCTION_NOT_DEFINED);
						}
						else if (!gradesheet.Contains(finishedToken))
						{
							this.ReportStatus(token, GraderState.ERROR_RUNTIME);
						}
						else
						{
							int runs = gradesheet.Split(new string[] { ranCorrectlyVar }, StringSplitOptions.None).Length - 1;
							int passes = gradesheet.Split(new string[] { answerCorrectVar }, StringSplitOptions.None).Length - 1;
							int fails = gradesheet.Split(new string[] { answerWrongVar }, StringSplitOptions.None).Length - 1;

							this.ReportConclusion(token, "SCORE," + runs + "," + passes + "," + fails, callback);
						}
					}
				}
				else
				{
					this.ReportConclusion(token, output, callback);
				}
			}
			else
			{
				this.ReportStatus(token, grader.State);
			}
		}

		private void ReportStatus(string token, GraderState state)
		{
			HttpRequest request = new HttpRequest("POST", "http://np10.nfshost.com/autograder/graderpoll/setstatus", new Dictionary<string, string>() {
				{ "token", token },
				{ "key", Util.HashWithSecret(token) },
				{ "status", state.ToString() },
			});

			request.Send();
		}

		private void ReportConclusion(string token, string output, string callbackArg)
		{
			// This will have to be different for actual grading.
			HttpRequest request = new HttpRequest("POST", "http://np10.nfshost.com/autograder/graderpoll/finish", new Dictionary<string, string>()
			{
				{ "token", token },
				{ "key", Util.HashWithSecret(token) },
				{ "output", output },
				{ "callback", callbackArg },
			});

			request.Send();

			string response = request.ResponseBody;
		}
	}
}
