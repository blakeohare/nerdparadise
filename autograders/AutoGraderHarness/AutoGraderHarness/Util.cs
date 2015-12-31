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

		private static Dictionary<char, int> HEX_VALUES = null;
		private static string secret;

		public static void Init(string secret)
		{
			Util.secret = secret;

			HEX_VALUES = new Dictionary<char, int>();
			// Explicitly initialize Util so that multithreaded processes don't try to at the same time.
			for (int i = 0; i < 16; ++i)
			{
				HEX_VALUES["0123456789abcdef"[i]] = i;
			}
		}

		public static string HexToString(string hex)
		{
			List<byte> bytes = new List<byte>();
			hex = hex.ToLowerInvariant().Trim();
			if (hex.Length % 2 == 1) hex = hex.Substring(0, hex.Length - 1);
			for (int i = 0; i < hex.Length; i += 2)
			{
				int a, b;
				if (HEX_VALUES.TryGetValue(hex[i], out a) && HEX_VALUES.TryGetValue(hex[i + 1], out b))
				{
					bytes.Add((byte)(a * 16 + b));
				}
			}
			return System.Text.Encoding.UTF8.GetString(bytes.ToArray());
		}

		public static string HashWithSecret(string value)
		{
			value += secret;
			byte[] bytes = value.Select<char, byte>(c => (byte)c).ToArray();
			byte[] encryptedBytes = new System.Security.Cryptography.SHA1Managed().ComputeHash(bytes);
			string[] hex = encryptedBytes.Select<byte, string>(b => b.ToString("X2")).ToArray();
			return string.Join("", hex);
		}
	}
}
