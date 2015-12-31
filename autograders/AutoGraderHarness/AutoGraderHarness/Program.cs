using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace AutoGraderHarness
{
	class Program
	{
		static void Main(string[] args)
		{
			System.Console.WriteLine("Enter secret:");
			string secret = "foo";// System.Console.ReadLine();

			WorkQueue queue = new WorkQueue();

			Util.Init(secret); // Do this now before multiple threads are involved.

			List<RunnerProcess> processes = new List<RunnerProcess>();
			for (int i = 0; i < Constants.MAX_CONCURRENT_THREADS; ++i)
			{
				RunnerProcess process = new RunnerProcess(queue);
				processes.Add(process);
				process.Start(i + 1);
			}

			double lastWorkFound = Now();
			while (true)
			{
				if (queue.PollForWork())
				{
					lastWorkFound = Now();
				}

				// Poll every second if recently used.
				double now = Now();
				if (now - 15 < lastWorkFound)
				{
					Sleep(1.0);
				}
				else if (now - 7 * 60 < lastWorkFound)
				{
					// every 5 seconds if used in the last 7 minutes
					Sleep(5.0);
				}
				else
				{
					// otherwise every 10 seconds.
					Sleep(10.0);
				}
			}

		}

		private static double Now()
		{
			return DateTime.Now.Ticks / 10000000.0;
		}

		private static void Sleep(double seconds)
		{
			int millis = (int)(1000 * seconds);
			while (millis > 0)
			{
				ThreadSafeConsoleWriter.Flush();
				if (millis > 50)
				{
					millis -= 50;
					System.Threading.Thread.Sleep(50);
				}
				else 
				{
					System.Threading.Thread.Sleep(millis);
				}
			}
		}
	}
}
