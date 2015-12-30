using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace AutoGraderHarness
{
	public class HttpRequest
	{
		public string Method { get; set; }
		public string URL { get; set; }
		public Dictionary<string, string> PostVars { get; set; }

		public bool IsDone { get { return this.ResponseCode != 0; } }
		public int ResponseCode { get; set; }
		public string ResponseBody { get; set; }

		public HttpRequest(string method, string url, Dictionary<string, string> postVars)
		{
			this.Method = method.Trim().ToUpperInvariant();
			this.URL = url;

			this.PostVars = postVars == null ? null :new Dictionary<string, string>(postVars);
			this.ResponseBody = null;
			this.ResponseCode = 0;
		}

		private static byte[] ConvertStringToBytes(string value)
		{
			byte[] bytes = new byte[value.Length * sizeof(char)];
			System.Buffer.BlockCopy(value.ToCharArray(), 0, bytes, 0, bytes.Length);
			return bytes;
		}

		private string ConvertKeyValuesToFormString(Dictionary<string, string> kvp)
		{
			StringBuilder output = new StringBuilder();
			bool isFirst = true;
			foreach (string key in kvp.Keys)
			{
				if (isFirst)
				{
					output.Append('&');
					isFirst = false;
				}

				output.Append(key);
				output.Append('=');

				byte[] valueBytes = ConvertStringToBytes(kvp[key]);
				int c;
				int length = valueBytes.Length;
				string hex = "0123456789ABCDEF";
				for (int i = 0; i < length; ++i)
				{
					c = valueBytes[i];
					if ((i >= 'a' && i <= 'z') ||
						(i >= 'A' && i <= 'Z') ||
						(i >= '0' && i <= '9'))
					{
						output.Append((char)c);
					}
					else if (c == ' ')
					{
						output.Append('+');
					}
					else
					{
						output.Append('%');
						output.Append(hex[c >> 4]);
						output.Append(hex[c & 15]);
					}
				}
			}

			return output.ToString();
		}

		public void Send()
		{
			if (this.Method == "GET")
			{
				System.Net.HttpWebRequest request = (System.Net.HttpWebRequest)System.Net.HttpWebRequest.Create(this.URL);
				request.Method = this.Method;
				request.UserAgent = "NP Autograder/1.0";

				if (this.PostVars != null)
				{
					string body = this.ConvertKeyValuesToFormString(this.PostVars);
					request.ContentLength = body.Length;

					request.ContentType = "application/x-www-form-urlencoded";

					System.IO.StreamWriter streamWriter = new System.IO.StreamWriter(request.GetRequestStream());
					streamWriter.Write(body);
					streamWriter.Flush();
					streamWriter.Close();
				}

				System.Net.HttpWebResponse response = (System.Net.HttpWebResponse)request.GetResponse();
				this.ResponseCode = (int)response.StatusCode;

				System.IO.StreamReader streamReader = new System.IO.StreamReader(response.GetResponseStream());
				this.ResponseBody = streamReader.ReadToEnd();
				streamReader.Close();
			}
		}
	}
}
