using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace AutoGraderHarness
{
	public enum GraderState
	{
		NOT_STARTED,
		SETTING_UP,
		RUNNING,
		DONE,

		ERROR_BANNED_CODE,
		ERROR_COMPILE,
		ERROR_RUNTIME,
		ERROR_TIMED_OUT,
		ERROR_MEMORY_EXCEEDED,
	}
}
