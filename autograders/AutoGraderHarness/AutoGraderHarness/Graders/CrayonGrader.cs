using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace AutoGraderHarness.Graders
{
	public class CrayonGrader : AbstractGrader
	{
		public CrayonGrader(string code) : base()
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

			if (output.Contains("start.cry, Line:"))
			{
				this.State = GraderState.ERROR_RUNTIME;
				return output;
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

			string output = CommandLineProcess.Run(this.WorkingDirectory, "crayon " + this.WorkingDirectory + "\\UserCode.build -target python");
			if (output.Length > 0)
			{
				this.State = GraderState.ERROR_COMPILE;
			}
		}
	}
}
