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
			return System.Text.Encoding.Default.GetBytes(value);
		}

		private string ConvertKeyValuesToFormString(Dictionary<string, string> kvp)
		{
			StringBuilder output = new StringBuilder();
			bool isFirst = true;
			foreach (string key in kvp.Keys)
			{
				if (isFirst)
				{
					isFirst = false;
				}
				else
				{
					output.Append('&');
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
					if ((c >= 'a' && c <= 'z') ||
						(c >= 'A' && c <= 'Z') ||
						(c >= '0' && c <= '9'))
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

		// TODO: build in retry mechanism
		public void Send()
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
			try
			{
				System.Net.HttpWebResponse response = (System.Net.HttpWebResponse)request.GetResponse();
				this.ResponseCode = (int)response.StatusCode;
				System.IO.StreamReader streamReader = new System.IO.StreamReader(response.GetResponseStream());
				this.ResponseBody = streamReader.ReadToEnd();
				streamReader.Close();
			}
			catch (System.Net.WebException we)
			{
				switch (we.Status)
				{
					case System.Net.WebExceptionStatus.Timeout:
					case System.Net.WebExceptionStatus.ConnectFailure:
					case System.Net.WebExceptionStatus.NameResolutionFailure:
						System.Threading.Thread.Sleep(10000);
						break;

					default:
						if (we.Response is System.Net.HttpWebResponse)
						{
							System.Net.HttpWebResponse exResponse = we.Response as System.Net.HttpWebResponse;
							this.ResponseBody = null;
							switch (exResponse.StatusCode)
							{
								case System.Net.HttpStatusCode.OK: this.ResponseCode = 200; break;
								case System.Net.HttpStatusCode.NotFound: this.ResponseCode = 404; break;
								case System.Net.HttpStatusCode.BadRequest: this.ResponseCode = 400; break;
								case System.Net.HttpStatusCode.InternalServerError: this.ResponseCode = 500; break;
								case System.Net.HttpStatusCode.ServiceUnavailable: this.ResponseCode = 503; break;
								default:
									// TODO: others if ever needed.
									break;
							}
							// TODO: how to get body?
						}
						else
						{
							throw new Exception();
						}
						break;
				}
			}
		}
	}
}
