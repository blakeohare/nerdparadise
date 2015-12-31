using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace AutoGraderHarness
{
	class ThreadSafeConsoleWriter
	{
		private static List<string> output = new List<string>();
		private static object mutex = new object();

		public static void Print(string value)
		{
			lock (mutex)
			{
				output.Add(value);
			}
		}

		public static void Flush()
		{
			string lines = null;
			lock (mutex)
			{
				if (output.Count > 0)
				{
					lines = string.Join("\n", output);
					output.Clear();
				}
			}

			if (lines != null)
			{
				System.Console.WriteLine(lines);
			}
		}
	}
}
