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
			HttpRequest request = new HttpRequest("GET", "http://nerdparadise.com", null);
			request.Send();
			string response = request.ResponseBody;
			int responsecode = request.ResponseCode;
		}
	}
}
