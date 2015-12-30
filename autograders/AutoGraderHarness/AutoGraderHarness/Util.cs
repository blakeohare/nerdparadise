using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace AutoGraderHarness
{
	public static class Util
	{
		private static readonly Random random = new Random();
		private static readonly string folderNameChars = "abcdefghijklmnopqrstuvwxyz0123456789";

		public static string GetGibberishString()
		{
			StringBuilder sb = new StringBuilder();
			for (int i = 0; i < 20; ++i)
			{
				sb.Append(folderNameChars[random.Next(folderNameChars.Length)]);
			}
			return sb.ToString();
		}

		public static void CreateDirectory(string path)
		{
			path = path.Replace('/', '\\');
			if (path == null || path.Length == 0 || path == ".") return;
			string parent = System.IO.Path.GetDirectoryName(path);
			if (!System.IO.Directory.Exists(parent)) CreateDirectory(parent);
			System.IO.Directory.CreateDirectory(path);
		}

		public static void CreateFile(string path, string content)
		{
			path = path.Replace('/', '\\');
			Util.CreateDirectory(System.IO.Path.GetDirectoryName(path));
			System.IO.File.WriteAllText(path, content);
		}
	}
}
