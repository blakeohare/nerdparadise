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

			Process p = new Process();
			p.StartInfo.UseShellExecute = false;
			p.StartInfo.RedirectStandardOutput = true;
			p.StartInfo.RedirectStandardError = true;
			p.StartInfo.WorkingDirectory = workingDirectory;

			p.StartInfo.FileName = file;
			p.StartInfo.Arguments = args;
			p.Start();

			string output = p.StandardOutput.ReadToEnd();
			output += p.StandardError.ReadToEnd();
			p.WaitForExit();
			
			return output.TrimEnd().Replace("\r\n", "\n");
		}
	}
}
