using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using AutoGraderHarness.Graders;

namespace AutoGraderHarness
{
	class Program
	{
		static void Main(string[] args)
		{
			CrayonGrader grader = new CrayonGrader(string.Join("\n", new string[] {
				"$print('Hello, World!');",
				"for (i = 0; i < 10; ++i) {",
				"  $print(i +  ' Mississippi');",
				"}",
				"$assert(false, 'keke');",
			}));

			grader.SetUp();
			if (grader.State == GraderState.SETTING_UP)
			{
				string output = grader.Run();
			}
		}
	}
}
