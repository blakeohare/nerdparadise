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
					HttpRequest request = new HttpRequest("POST", "http://np10.nfshost.com/autograder/poll/claim" , new Dictionary<string, string>()
					{
						{ "key", Util.HashWithSecret(token) },
						{ "token", token },
					});
					ThreadSafeConsoleWriter.Print("[" + token + "] Claiming...");
					request.Send();

					if (request.ResponseCode == 200)
					{
						string[] data = request.ResponseBody.Trim().Split(',');
						if (data[0] == "OK" && data.Length == 6)
						{
							string language = Util.HexToString(data[1]);
							string code = Util.HexToString(data[2]);
							string callback = Util.HexToString(data[3]);
							string tests = Util.HexToString(data[4]);
							string feature = Util.HexToString(data[5]);

							ThreadSafeConsoleWriter.Print("[" + token + "] running " + language + " entry for " + feature + ".");
							switch (language)
							{
								case "crayon":
									this.RunCrayonCode(token, code, callback, tests, feature);
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

		private void RunCrayonCode(string token, string code, string callback, string tests, string feature)
		{
			CrayonGrader grader = new CrayonGrader(code);

			grader.SetUp();

			if (grader.State == GraderState.SETTING_UP)
			{
				grader.State = GraderState.RUNNING;
				this.ReportStatus(token, grader.State);
				string output = grader.Run();
				this.ReportConclusion(token, output, callback);
			}
			else
			{
				this.ReportStatus(token, grader.State);
			}
		}

		private void ReportStatus(string token, GraderState state)
		{
			HttpRequest request = new HttpRequest("POST", "http://np10.nfshost.com/autograder/poll/setstatus", new Dictionary<string,string>() {
				{ "token", token },
				{ "key", Util.HashWithSecret(token) },
				{ "status", state.ToString() },
			});

			request.Send();
		}

		private void ReportConclusion(string token, string output, string callbackArg)
		{
			// This will have to be different for actual grading.
			HttpRequest request = new HttpRequest("POST", "http://np10.nfshost.com/autograder/poll/finish", new Dictionary<string, string>()
			{
				{ "token", token },
				{ "key", Util.HashWithSecret(token) },
				{ "output", output },
				{ "callback", callbackArg },
			});

			request.Send();
		}
	}
}
