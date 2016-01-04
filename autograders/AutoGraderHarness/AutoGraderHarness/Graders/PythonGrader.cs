using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace AutoGraderHarness.Graders
{
	class PythonGrader : AbstractGrader
	{
		public PythonGrader(string code)
			: base()
		{
			this.Code = code.Replace("\r\n", "\n").Replace('\r', '\n');
		}

		public override void SetUp()
		{
			this.State = GraderState.SETTING_UP;
			if (this.Code.Length == 0)
			{
				this.Code = "pass";
			}
			else
			{
				char c = this.Code[0];
				if (c == ' ' || c == '\t')
				{
					this.State = GraderState.ERROR_COMPILE;
				}
				else
				{
					this.CheckForBannedFunctions();
				}
			}
		}

		private void CheckForBannedFunctions()
		{
			string wrapperName = Util.GetGibberishString();

			List<string> wrappedCode = new List<string>() { "def " + wrapperName + "():" };
			foreach (string line in this.Code.Split('\n'))
			{
				wrappedCode.Add("  " + line);
			}

			wrappedCode.AddRange(new string[] {
				"",
				"import dis",
				"",
				"dis.dis(" + wrapperName + ")"
			});

			Util.CreateFile(this.WorkingDirectory + "\\banned_code_check.py", string.Join("\n", wrappedCode));

			string output = CommandLineProcess.Run(this.WorkingDirectory, @"C:\Python34\python banned_code_check.py");

			foreach (string bannedItem in new string[] { 
				"IMPORT_NAME",
			})
			{
				if (output.Contains(bannedItem))
				{
					this.State = GraderState.ERROR_BANNED_CODE;
					return;
				}
			}

			HashSet<string> allDefaultGlobals = new HashSet<string>(new string[] { 
				// Copied directly from the output of dir()
				"__builtins__", "__doc__", "__loader__", "__name__", "__package__", "__spec__",
				// Copied directly from the output of __builtins__
				"ArithmeticError", "AssertionError", "AttributeError", "BaseException", "BlockingIOError", "BrokenPipeError", "BufferError", "BytesWarning", "ChildProcessError", "ConnectionAbortedError", "ConnectionError", "ConnectionRefusedError", "ConnectionResetError", "DeprecationWarning", "EOFError", "Ellipsis", "EnvironmentError", "Exception", "False", "FileExistsError", "FileNotFoundError", "FloatingPointError", "FutureWarning", "GeneratorExit", "IOError", "ImportError", "ImportWarning", "IndentationError", "IndexError", "InterruptedError", "IsADirectoryError", "KeyError", "KeyboardInterrupt", "LookupError", "MemoryError", "NameError", "None", "NotADirectoryError", "NotImplemented", "NotImplementedError", "OSError", "OverflowError", "PendingDeprecationWarning", "PermissionError", "ProcessLookupError", "ReferenceError", "ResourceWarning", "RuntimeError", "RuntimeWarning", "StopIteration", "SyntaxError", "SyntaxWarning", "SystemError", "SystemExit", "TabError", "TimeoutError", "True", "TypeError", "UnboundLocalError", "UnicodeDecodeError", "UnicodeEncodeError", "UnicodeError", "UnicodeTranslateError", "UnicodeWarning", "UserWarning", "ValueError", "Warning", "WindowsError", "ZeroDivisionError", "_", "__build_class__", "__debug__", "__doc__", "__import__", "__loader__", "__name__", "__package__", "__spec__", "abs", "all", "any", "ascii", "bin", "bool", "bytearray", "bytes", "callable", "chr", "classmethod", "compile", "complex", "copyright", "credits", "delattr", "dict", "dir", "divmod", "enumerate", "eval", "exec", "exit", "filter", "float", "format", "frozenset", "getattr", "globals", "hasattr", "hash", "help", "hex", "id", "input", "int", "isinstance", "issubclass", "iter", "len", "license", "list", "locals", "map", "max", "memoryview", "min", "next", "object", "oct", "open", "ord", "pow", "print", "property", "quit", "range", "repr", "reversed", "round", "set", "setattr", "slice", "sorted", "staticmethod", "str", "sum", "super", "tuple", "type", "vars", "zip"
			});

			HashSet<string> theWhiteList = new HashSet<string>(new string[] {
				"abs", "all", "any", "ascii", "bin", "bool", "bytearray", "bytes", "callable", "chr", "complex", "dict", "divmod", "enumerate", "filter", "float", "format", "frozenset", "hash",
				"hex", "id", "int", "isinstance", "issubclass", "iter", "len", "list", "map", "max", "min", "next", "object", "oct", "ord", "pow", "print", "property", "range", "repr", "reversed",
				"round", "set", "slice", "sorted", "staticmethod", "str", "sum", "super", "tuple", "type", "zip"
			});

			

			string[] lines = output.Split('\n');
			HashSet<string> globalsLoaded = new HashSet<string>();
			foreach (string line in lines)
			{
				string[] parts = line.Replace(")", "").Split('(');
				string arg = parts[parts.Length - 1].Trim();
				if (line.Contains("LOAD_GLOBAL"))
				{
					if (allDefaultGlobals.Contains(arg) && !theWhiteList.Contains(arg))
					{
						this.State = GraderState.ERROR_BANNED_CODE;
						return;
					}
				}
				else if (line.Contains("LOAD_ATTR"))
				{
					if (arg.StartsWith("__"))
					{
						this.State = GraderState.ERROR_BANNED_CODE;
						return;
					}
				}
			}

		}

		public string Run()
		{
			Util.CreateFile(this.WorkingDirectory + "\\run_me.py", this.Code);
			return CommandLineProcess.Run(this.WorkingDirectory, @"C:\Python34\python run_me.py");
		}

		// TODO: refactor this as well.
		protected override void ConvertJsonToCode(object value, StringBuilder sb)
		{
			if (value == null) sb.Append("None");
			else if (value is bool) sb.Append((bool)value);
			else if (value is string)
			{
				string str = value.ToString();
				sb.Append('"');
				for (int j = 0; j < str.Length; ++j)
				{
					switch (str[j])
					{
						case '\n': sb.Append("\\n"); break;
						case '\r': sb.Append("\\r"); break;
						case '\"': sb.Append("\\\""); break;
						case '\\': sb.Append("\\\\"); break;
						case '\0': sb.Append("\\0"); break;
						default: sb.Append(str[j]); break;
					}
				}
				sb.Append('"');
			}
			else if (value is List<object>)
			{
				List<object> list = (List<object>)value;
				sb.Append("[");
				for (int i = 0; i < list.Count; ++i)
				{
					if (i > 0) sb.Append(", ");
					ConvertJsonToCode(list[i], sb);
				}
				sb.Append("]");
			}
			else if (value is Dictionary<string, object>)
			{
				Dictionary<string, object> dict = (Dictionary<string, object>)value;
				sb.Append("{");
				bool first = true;
				foreach (string key in dict.Keys)
				{
					if (first) first = false;
					else sb.Append(", ");

					ConvertJsonToCode(key, sb);
					sb.Append(": ");
					ConvertJsonToCode(dict[key], sb);
				}
				sb.Append("}");
			}
			else
			{
				// numbers
				sb.Append(value.ToString());
			}
		}
	}
}
