using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace AutoGraderHarness.Graders
{
	public abstract class AbstractGrader
	{
		public string Code { get; set; }
		public string Sandbox { get; set; }
		public string WorkingDirectory { get; set; }

		private object mutex = new object();

		private GraderState state = GraderState.NOT_STARTED;

		public AbstractGrader()
		{
			this.Sandbox = Util.GetGibberishString();
			this.WorkingDirectory = System.IO.Path.GetFullPath(".\\" + this.Sandbox + "\\" + this.Sandbox);
		}

		public GraderState State
		{
			get
			{
				GraderState output;
				lock (mutex)
				{
					output = this.state;
				}
				return output;
			}

			set
			{
				lock (mutex)
				{
					this.state = value;
				}
			}
		}

		public abstract void SetUp();
	}
}
