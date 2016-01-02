using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace AutoGraderHarness
{
	internal static class JsonParser
	{
		private class JsonParserException : Exception { }

		private class Index
		{
			public int Value;
		}

		public static object ParseJsonIntoValue(string rawValue)
		{
			try
			{
				return ParseJsonIntoValue(rawValue.ToCharArray(), rawValue.Length, new Index() { Value = 0 });
			}
			catch (JsonParserException)
			{
				return null;
			}
		}

		private static object ParseJsonIntoValue(char[] rawValue, int length, Index i)
		{
			object output = ParseJsonThing(rawValue, length, i);
			SkipWhitespace(rawValue, length, i);
			if (i.Value < length) throw new JsonParserException();
			return output;
		}

		private static object ParseJsonThing(char[] rawValue, int length, Index i)
		{
			SkipWhitespace(rawValue, length, i);
			object value = null;
			char c = rawValue[i.Value];
			if (c == '{')
			{
				value = ParseJsonDictionary(rawValue, length, i);
			}
			else if (c == '[')
			{
				value = ParseJsonList(rawValue, length, i);
			}
			else if (c == '"')
			{
				value = ParseJsonString(rawValue, length, i);
			}
			else if (c == '.' || c == '-' || (c >= '0' && c <= '9'))
			{
				value = ParseJsonNumber(rawValue, length, i);
			}
			else if (PopIfPresent(rawValue, length, i, "true"))
			{
				value = true;
			}
			else if (PopIfPresent(rawValue, length, i, "false"))
			{
				value = false;
			}
			else if (PopIfPresent(rawValue, length, i, "null"))
			{
				value = null;
			}
			else
			{
				throw new JsonParserException();
			}
			return value;
		}

		private static object ParseJsonNumber(char[] rawValue, int length, Index i)
		{
			char c;
			StringBuilder sb = new StringBuilder();
			while (i.Value < length)
			{
				c = rawValue[i.Value++];

				if (c != '.' && c != '-' && (c < '0' || c > '9'))
				{
					i.Value--;
					break;
				}
				else
				{
					sb.Append(c);
				}
			}

			string stringValue = sb.ToString();
			if (stringValue.Contains('.'))
			{
				double value;
				if (double.TryParse(stringValue, out value))
				{
					return value;
				}
			}
			else
			{
				int value;
				if (int.TryParse(stringValue, out value))
				{
					return value;
				}
			}

			throw new JsonParserException();
		}

		private static object ParseJsonString(char[] rawValue, int length, Index i)
		{
			i.Value++; // opening quote
			StringBuilder sb = new StringBuilder();
			while (i.Value < length && rawValue[i.Value] != '"')
			{
				char c = rawValue[i.Value++];
				if (c == '\\')
				{
					switch (rawValue[i.Value++])
					{
						case '\\': c = '\\'; break;
						case '"': c = '"'; break;
						case '\'': c = '\''; break;
						case 'n': c = '\n'; break;
						case 't': c = '\t'; break;
						case 'r': c = '\r'; break;
						case '0': c = '\0'; break;
						default: throw new JsonParserException();
					}
				}
				sb.Append(c);
			}

			if (i.Value >= length) throw new JsonParserException();
			i.Value++; // closing quote
			return sb.ToString();
		}

		private static object ParseJsonList(char[] rawValue, int length, Index i)
		{
			i.Value++; // '['
			SkipWhitespace(rawValue, length, i);
			List<object> items = new List<object>();
			while (i.Value < length && rawValue[i.Value] != ']')
			{
				if (items.Count > 0)
				{
					PopExpected(rawValue, length, i, ",");
					SkipWhitespace(rawValue, length, i);
				}

				object item = ParseJsonThing(rawValue, length, i);
				SkipWhitespace(rawValue, length, i);
				items.Add(item);
			}

			if (i.Value < length) PopExpected(rawValue, length, i, "]");

			return new List<object>(items);
		}

		private static object ParseJsonDictionary(char[] rawValue, int length, Index i)
		{
			i.Value++; // '{'
			SkipWhitespace(rawValue, length, i);
			List<object> keys = new List<object>();
			List<object> values = new List<object>();
			while (i.Value < length && rawValue[i.Value] != '}')
			{
				if (keys.Count > 0)
				{
					PopExpected(rawValue, length, i, ",");
					SkipWhitespace(rawValue, length, i);
				}

				object key = ParseJsonThing(rawValue, length, i);
				if (!(key is string)) throw new JsonParserException();
				SkipWhitespace(rawValue, length, i);
				PopExpected(rawValue, length, i, ":");
				SkipWhitespace(rawValue, length, i);
				object value = ParseJsonThing(rawValue, length, i);
				SkipWhitespace(rawValue, length, i);
				keys.Add(key);
				values.Add(value);
			}

			if (i.Value < length)
			{
				i.Value++; // '}'
			}
			else
			{
				throw new JsonParserException(); // EOF
			}

			return WrapDictionary(keys, values);
		}

		private static void PopExpected(char[] rawValue, int length, Index index, string value)
		{
			if (!PopIfPresent(rawValue, length, index, value))
			{
				throw new JsonParserException();
			}
		}

		private static bool PopIfPresent(char[] rawValue, int length, Index index, string value)
		{
			if (index.Value + value.Length > length)
			{
				return false;
			}

			for (int i = 0; i < value.Length; ++i)
			{
				if (rawValue[index.Value + i] != value[i])
				{
					return false;
				}
			}
			index.Value += value.Length;
			return true;
		}

		private static readonly HashSet<char> WHITESPACE = new HashSet<char>(" \t\r\n".ToCharArray());
		private static void SkipWhitespace(char[] rawValue, int length, Index i)
		{
			while (i.Value < length && WHITESPACE.Contains(rawValue[i.Value]))
			{
				i.Value++;
			}
		}

		private static object WrapDictionary(List<object> keys, List<object> values)
		{
			Dictionary<string, object> output = new Dictionary<string, object>();

			for (int i = 0; i < keys.Count; ++i)
			{
				string key = keys[i].ToString();
				output[key] = values[i];
			}
			return output;
		}
	}
}
