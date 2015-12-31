using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace AutoGraderHarness
{
	class WorkQueue
	{
		private object mutex = new object();

		private List<string> responses = new List<string>();

		// return true if work found.
		public bool PollForWork()
		{
			bool output = false;
			HttpRequest request = new HttpRequest("GET", "http://np10.nfshost.com/autograder/poll", null);
			request.Send();
			if (request.ResponseCode == 200)
			{
				// Will have a list of commad-delimited strings. First one is always "OK"
				// Next item is the number (n) of tokens that need to be run.
				// The next n items are those tokens
				string[] things = request.ResponseBody.Trim().Split(',');
				if (things.Length > 1)
				{
					if (things[0] == "OK")
					{
						int tokenCount;
						if (int.TryParse(things[1], out tokenCount) && tokenCount == things.Length - 2)
						{
							lock (mutex)
							{
								for (int i = 0; i < tokenCount; ++i)
								{
									responses.Add(things[i + 2].Trim());
								}
							}
							output = true;
						}
					}
				}
			}
			return output;
		}

		public string PopWorkItem()
		{
			string output = null;
			lock (mutex)
			{
				if (this.responses.Count > 0)
				{
					output = this.responses[0];
					this.responses.RemoveAt(0);
				}
			}
			return output;
		}
	}
}
