using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace AutoGraderHarness.Graders
{
	public class CrayonGrader : AbstractGrader
	{
		public CrayonGrader(string code)
			: base()
		{
			this.Code = code;
			this.State = GraderState.NOT_STARTED;
		}

		public override void SetUp()
		{
			this.State = GraderState.SETTING_UP;
			this.CheckForBannedFunctions();
			if (this.State != GraderState.SETTING_UP) return;
			this.CompileCrayonToPython();
			if (this.State != GraderState.SETTING_UP) return;
		}

		public string Run()
		{
			this.State = GraderState.RUNNING;

			string output = CommandLineProcess.Run(System.IO.Path.Combine(this.WorkingDirectory, "output", "python"), "python game.py");
			if (output == null)
			{
				this.State = GraderState.ERROR_TIMED_OUT;
				return null;
			}
			else if (output.Contains("start.cry, Line:"))
			{
				this.State = GraderState.ERROR_RUNTIME;
				return output;
			}
			else if (output.Contains("Traceback (most recent call last)") && output.Contains("MemoryError"))
			{
				this.State = GraderState.ERROR_MEMORY_EXCEEDED;
				return null;
			}
			else
			{
				this.State = GraderState.DONE;
			}
			return output;
		}

		private void CheckForBannedFunctions()
		{
			foreach (string bannedPrefix in new string[] {
				"$user_data",
				"$io_",
				"$launch_browser",
				"$http_",
				"$gamepad_",
				"$gfx_",
				"$sfx_",
				"$music_",
				"$game_",
			})
			{
				if (this.Code.Contains(bannedPrefix))
				{
					this.State = GraderState.ERROR_BANNED_CODE;
					return;
				}
			}
		}

		private void CompileCrayonToPython()
		{
			Util.CreateFile(this.WorkingDirectory + "\\UserCode.build", string.Join("\n",
				"<build>",
				"  <projectname>UserCode</projectname>",
				"  <source>source/</source>",
				"  <output>output/%TARGET_NAME%</output>",
				"  <target name=\"python\">",
				"    <platform>python</platform>",
				"  </target>",
				"</build>"));
			Util.CreateFile(this.WorkingDirectory + "/source/start.cry", this.Code);

			string output = CommandLineProcess.Run(
				this.WorkingDirectory, 
				"crayon " + this.WorkingDirectory + "\\UserCode.build -target python");

			if (output.Length > 0)
			{
				this.State = GraderState.ERROR_COMPILE;
			}
		}

		protected override void ConvertJsonToCode(object value, StringBuilder sb)
		{
			if (value == null) sb.Append("null");
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
