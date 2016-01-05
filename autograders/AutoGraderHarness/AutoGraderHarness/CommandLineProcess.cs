using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Diagnostics;

namespace AutoGraderHarness
{
	public class CommandLineProcess
	{
		public static string Run(string workingDirectory, string command)
		{
			string[] parts = command.Split(' ');
			string file = parts[0];
			string args = command.Substring(file.Length + 1);

			List<string> errorOutput = new List<string>();
			List<string> stdOutput = new List<string>();

			ProcessStartInfo pstartInfo = new ProcessStartInfo(file, args);
			pstartInfo.ErrorDialog = false;
			pstartInfo.UseShellExecute = false;
			pstartInfo.RedirectStandardError = true;
			pstartInfo.RedirectStandardOutput = true;
			pstartInfo.WorkingDirectory = workingDirectory;
			Process p = new Process();
			p.StartInfo = pstartInfo;
			p.ErrorDataReceived += (sender, err) => { if (err.Data != null) errorOutput.Add(err.Data); };
			p.OutputDataReceived += (sender, text) => { if (text.Data != null) stdOutput.Add(text.Data); };
			p.Start();
			p.BeginErrorReadLine();
			p.BeginOutputReadLine();

			if (!p.WaitForExit(10 * 1000))
			{
				p.Kill();
				return null;
			}

			string output = string.Join("\n", stdOutput.Concat<string>(errorOutput)).Replace("\r\n", "\n");

			return output;
		}
	}
}
