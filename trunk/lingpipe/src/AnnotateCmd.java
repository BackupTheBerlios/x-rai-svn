/*
 * LingPipe v. 1.0
 * Copyright (C) 2003 Alias-i
 *
 * This program is licensed under the Alias-i Royalty Free License
 * Version 1 WITHOUT ANY WARRANTY, without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the Alias-i
 * Royalty Free License Version 1 for more details.
 *
 * You should have received a copy of the Alias-i Royalty Free License
 * Version 1 along with this program; if not, visit
 * http://www.alias-i.com/lingpipe/licenseV1.txt or contact
 * Alias-i, Inc. at 181 North 11th Street, Suite 401, Brooklyn, NY 11211,
 * +1 (718) 290-9170.
 */

import java.io.BufferedOutputStream;
import java.io.File;
import java.io.FileFilter;
import java.io.FileOutputStream;
import java.io.UnsupportedEncodingException;
import java.util.Stack;

import org.apache.xerces.parsers.SAXParser;
import org.xml.sax.Attributes;
import org.xml.sax.InputSource;
import org.xml.sax.SAXException;
import org.xml.sax.XMLReader;
import org.xml.sax.helpers.XMLReaderFactory;

import com.aliasi.coref.MentionFactory;
import com.aliasi.coref.WithinDocCorefAnnotateFilter;
import com.aliasi.io.FileExtensionFilter;
import com.aliasi.sentences.SentenceAnnotateFilter;
import com.aliasi.sentences.SentenceModel;
import com.aliasi.tokenizer.TokenizerFactory;
import com.aliasi.util.Arrays;
import com.aliasi.util.Files;
import com.aliasi.util.Reflection;
import com.aliasi.util.Streams;
import com.aliasi.xml.GroupCharactersFilter;
import com.aliasi.xml.SAXWriter;

class INEXAttributes extends org.xml.sax.helpers.AttributesImpl {
	public INEXAttributes() {
		super();
	}
}

// SAX handler
// Encloses every text() node into a xrai:s, unless the element contains only
// text
// B. Piwowarski


class INEXSAXWriter2 extends com.aliasi.xml.SAXWriter {
	public INEXSAXWriter2(BufferedOutputStream bufOut, String outputCharset) throws UnsupportedEncodingException {
		super(bufOut, outputCharset);
	}
}
class INEXSAXWriter extends com.aliasi.xml.SAXWriter {
	/**
	 * 
	 */
	private static final String XRAI_S = "xrai:s";

	static class Container {
		char[] chars;

		int start, length;

		public Container(char[] chars, int start, int length) {
			this.chars = chars;
			this.start = start;
			this.length = length;
		}

	}

	/** Our mutable boolean */
	static class MutableBoolean {
		public boolean value = false;

		MutableBoolean(boolean value) {
			this.value = value;
		}
	}

	// The current CDATA
	Stack<Container> cdata = new Stack<Container>();

	// True if we know that the current element has text
	Stack<MutableBoolean> hasText = new Stack<MutableBoolean>();

	// True if we know that the current element has a child
	Stack<MutableBoolean> hasChild = new Stack<MutableBoolean>();

	// True if the last element was an xrai:s (useful to include some
	// whitespaces into the xrai:s element)
	Stack<MutableBoolean> inXRaiSentence = new Stack<MutableBoolean>();

	public INEXSAXWriter(BufferedOutputStream bufOut, String outputCharset)
			throws java.io.UnsupportedEncodingException {
		super(bufOut, outputCharset);
	}

	
	protected boolean isSpace(char[] ch, int start, int length) {
		for (int i = 0; i < length; i++)
			if (!Character.isWhitespace(ch[i]))
				return false;
		return true;
	}

	public void characters(char[] ch, int start, int length) {
		// The current containing node has text 
		hasText.peek().value = true;

		// If the last element was xrai:s and we have only whitespace,
		// include it; otherwise, end the xrai:s element
		if (inXRaiSentence.peek().value) {
			if (!isSpace(ch, start, length)) {
				print_cdata(XRaiWrap.NONE);
				super.endElement(null, XRAI_S, XRAI_S);
				inXRaiSentence.peek().value = false;
			}
		}

		// Add the cdata
		cdata.add(new Container(ch, start, length));
	}

	public void endDocument() {
		super.endDocument();
	}

	enum XRaiWrap {
		NONE, START, BOTH
	}
	
	void print_cdata(XRaiWrap wrap) {
		if (cdata.empty())
			return;

		if (wrap != XRaiWrap.NONE)
			super.startElement(null, XRAI_S, XRAI_S, new INEXAttributes());
		
		for (Container c : cdata) {
			super.characters(c.chars, c.start, c.length);
		}
		if (wrap == XRaiWrap.START) {
			inXRaiSentence.peek().value = true;
		} else if (wrap == XRaiWrap.BOTH) {
			super.endElement(null, XRAI_S, XRAI_S);			
		}
		cdata.clear();
	}

	public void startDocument() {
		super.startDocument();
		hasText.clear();
		hasChild.clear();
		cdata.clear();
		inXRaiSentence.clear();
		super.characters("\n".toCharArray(), 0, 1);
	}

//	final void debug(String s) {
//		super.characters(s.toCharArray(), 0, s.length());		
//	}
	public void startElement(String namespaceURI, String localName,
			String qName, Attributes atts) {
		if (!hasText.empty()) {
			
			if (inXRaiSentence.peek().value) {
				print_cdata(XRaiWrap.NONE);
				super.endElement(namespaceURI, XRAI_S, XRAI_S);
				inXRaiSentence.peek().value = false;
			}

			if (hasText.peek().value) {
				hasChild.peek().value = true;
				// we print wraping up things
				print_cdata(XRaiWrap.BOTH);
			}


		}
		hasText.push(new MutableBoolean(false));
		hasChild.push(new MutableBoolean(false));
		inXRaiSentence.push(new MutableBoolean(false));
		super.startElement(namespaceURI, localName, qName, atts);
	}

	public void endElement(String namespaceURI, String localName, String qName) {
		// Display the </xrai:s> if needed
		if (inXRaiSentence.peek().value) {
			print_cdata(XRaiWrap.NONE);
			super.endElement(namespaceURI, XRAI_S, XRAI_S);
			inXRaiSentence.peek().value = false;
		}
		// wrap only if needed
		else print_cdata(hasChild.peek().value && hasText.peek().value ? XRaiWrap.BOTH : XRaiWrap.NONE);

		hasText.pop();
		hasChild.pop();
		inXRaiSentence.pop();
		
		// If we are in an xrai:s element, do not print it
		// but rather tell we are in an xrai:s element
		if (localName.equals(XRAI_S)) {
			inXRaiSentence.peek().value = true;
		} else
			super.endElement(namespaceURI, localName, qName);
	}

}

/**
 * @author Bob Carpenter, Breck Baldwin
 * @version 2.0
 * @since LingPipe2.0
 */
public class AnnotateCmd extends AbstractEntityCmd {

	private AnnotateCmd(String[] args) {
		super(args);
		addDefaultProperty(MENTION_FACTORY_PARAM, DEFAULT_MENTION_FACTORY);
		addDefaultProperty(PRONOUN_DICTIONARY_PARAM, DEFAULT_PRONOUN_DICTIONARY);
		addDefaultProperty(SENTENCE_MODEL_PARAM, DEFAULT_SENTENCE_MODEL);
	}

	/**
	 * Runs the named entity annotation command.
	 */
	public void run() {
		File inputDir = getArgumentDirectory(INPUT_DIRECTORY_PARAM);
		System.out.println("Input directory=" + inputDir);

		File outputDir = getOrCreateArgumentDirectory(OUTPUT_DIRECTORY_PARAM);

		System.out.println("Output directory=" + outputDir);

		String outputCharset = getOutputCharset();

		SentenceAnnotateFilter sentenceFilter = getSentenceAnnotateFilter();

		// NEAnnotateFilter neFilter = getAnnotationFilter(true);

		MentionFactory mentionFactory = getMentionFactory();
		WithinDocCorefAnnotateFilter corefFilter = new WithinDocCorefAnnotateFilter(
				mentionFactory);

		sentenceFilter.setHandler(new GroupCharactersFilter(corefFilter)); // new
		// GroupCharactersFilter(neFilter));

		// neFilter.setHandler(new GroupCharactersFilter(corefFilter));

		File[] inFiles = null;
		if (hasArgument(FILE_EXTENSIONS_PARAM)) {
			String[] fileSuffixes = getCSV(FILE_EXTENSIONS_PARAM);
			System.out.println("Annotate files with suffixes="
					+ Arrays.arrayToString(fileSuffixes));
			FileFilter extensionFilter = new FileExtensionFilter(fileSuffixes,
					false);
			inFiles = inputDir.listFiles(extensionFilter);
		} else {
			inFiles = inputDir.listFiles(Files.FILES_ONLY_FILE_FILTER);
		}
		System.out.println("Found files to process." + " File count="
				+ inFiles.length);

		String inputCharset = getContentCharset();

		String contentType = getContentType();
		XMLReader reader = null;
		if (!contentType.equals(PLAIN_INPUT_TYPE)) {
			if (contentType.equals(XML_INPUT_TYPE)) {
				try {
					reader = XMLReaderFactory.createXMLReader();
				} catch (SAXException e) {
					String msg = "Could not create XML reader." + " Exception="
							+ e;
					throw new RuntimeException(msg);
				}
			} else { // HTML
				reader = new SAXParser();
			}
			// No namespace support !!!
			try {
				reader.setFeature("http://xml.org/sax/features/namespaces",
						false);
				reader
						.setFeature(
								"http://xml.org/sax/features/namespace-prefixes",
								false);
			} catch (org.xml.sax.SAXException e) {
				System.err.println(e);
				System.exit(-1);
			}
			reader.setContentHandler(new GroupCharactersFilter(sentenceFilter));
		}

		for (int i = 0; i < inFiles.length; ++i) {
			System.out.println("Processing file=" + inFiles[i]);
			String outName = inFiles[i].getName();
			if (!outName.endsWith(".xml"))
				outName = outName + ".xml";
			File outFile = new File(outputDir, outName);
			FileOutputStream out = null;
			BufferedOutputStream bufOut = null;
			try {
				out = new FileOutputStream(outFile);
				bufOut = new BufferedOutputStream(out);
				SAXWriter saxWriter = new INEXSAXWriter(bufOut, outputCharset);
				corefFilter.setHandler(saxWriter);
				if (reader != null) {
					InputSource in = new InputSource(inFiles[i].toString());
					in.setEncoding(inputCharset);
					reader.parse(in);
				} else {
					char[] chars = Files.readCharsFromFile(inFiles[i],
							inputCharset);
					sentenceFilter.startDocument();
					sentenceFilter
							.startSimpleElement(PLAIN_TEXT_TOP_LEVEL_ELEMENT);
					sentenceFilter.filteredCharacters(chars, 0, chars.length);
					sentenceFilter
							.endSimpleElement(PLAIN_TEXT_TOP_LEVEL_ELEMENT);
					sentenceFilter.endDocument();
				}
			} catch (Exception e) {
				System.out
						.println("Exception processing file. Ignoring output.");
				System.out.println("     File=" + inFiles[i]);
				System.out.println("     Exception=" + e);
				e.printStackTrace(System.err);
				try {
					outFile.delete();
				} catch (Exception e2) {
					System.out
							.println("Could not delete potentially corrupted output file.");
					System.out.println("    File=" + outFile);
					System.out.println("    Exception=" + e2);
				}
			} finally {
				Streams.closeOutputStream(bufOut);
				Streams.closeOutputStream(out);
			}
		}
	}

	/**
	 * Runs the command on the specified argument array. See the class
	 * documentation for information on the arguments.
	 * 
	 * @param args
	 *            Command-line arguments.
	 */
	public static void main(String[] args) {
		new AnnotateCmd(args).run();
	}

	MentionFactory getMentionFactory() {

		String mentionFactoryClassName = getExistingArgument(MENTION_FACTORY_PARAM);
		System.out.println("Mention Factory Class=" + mentionFactoryClassName);
		MentionFactory mentionFactory = null;
		try {
			mentionFactory = (MentionFactory) Reflection
					.newInstance(mentionFactoryClassName);
		} catch (ClassCastException e) {
			illegalPropertyArgument("Exception casting to interface="
					+ "com.aliasi.coref.MentionFactory.", MENTION_FACTORY_PARAM);
		} catch (IllegalArgumentException e) {
			illegalPropertyArgument("Exception constructing mention factory="
					+ e.getMessage(), MENTION_FACTORY_PARAM);
		}
		if (mentionFactory == null)
			illegalPropertyArgument("Could not construct mention factory.",
					MENTION_FACTORY_PARAM);
		return mentionFactory;
	}

	/**
	 * Returns the specified or the default sentence annotation filter.
	 * 
	 * @throws IllegalArgumentException
	 *             If there is an exception constructing the annotation filter
	 *             from the command-line arguments.
	 */
	protected SentenceAnnotateFilter getSentenceAnnotateFilter() {

		TokenizerFactory tokenizerFactory = getTokenizerFactory();

		SentenceModel sentenceModel = getSentenceModel();
		SentenceAnnotateFilter sentFilter = null;

		if (hasArgument("elements")) {
			String[] elements = getCSV("elements");
			System.out.println("Elements to annotate for sentences="
					+ Arrays.arrayToString(elements));
			sentFilter = new SentenceAnnotateFilter(sentenceModel,
					tokenizerFactory, elements);
		} else {
			System.out.println("Annotating all elements.");
			sentFilter = new SentenceAnnotateFilter(sentenceModel,
					tokenizerFactory);
		}
		if (sentFilter == null)
			illegalPropertyArgument(
					"Could not construct sentence filter from model.",
					SENTENCE_MODEL_PARAM);
		return sentFilter;
	}

	/**
	 * Returns the sentence model for this command.
	 * 
	 * @return Sentence model for this command.
	 */
	protected SentenceModel getSentenceModel() {
		String sentenceModelClassName = getExistingArgument(SENTENCE_MODEL_PARAM);
		System.out.println("Sentence Model=" + sentenceModelClassName);
		SentenceModel model = null;
		try {
			model = (SentenceModel) Reflection
					.newInstance(sentenceModelClassName);
		} catch (ClassCastException e) {
			illegalPropertyArgument("Sentence model must extend class="
					+ SentenceModel.class, SENTENCE_MODEL_PARAM);

		} catch (IllegalArgumentException e) {
			illegalPropertyArgument(
					"Error constructing sentence model."
							+ "  Make sure the requested sentence class is on the classpath and has a nullary (zero argument) constructor."
							+ "\n Exception=" + e.getMessage(),
					SENTENCE_MODEL_PARAM);
		}
		if (model == null)
			illegalPropertyArgument(
					"Could not construct sentence model."
							+ " Make sure the requested sentence class is on the classpath and has a nullary (zero argument) constructor.",
					SENTENCE_MODEL_PARAM);
		return model;
	}

	static String SENTENCE_MODEL_PARAM = "sentenceModel";

	static String DEFAULT_SENTENCE_MODEL = "com.aliasi.sentences.IndoEuropeanSentenceModel";

	static String MENTION_FACTORY_PARAM = "mentionFactory";

	static String DEFAULT_MENTION_FACTORY = "com.aliasi.coref.EnglishMentionFactory";

	static String DEFAULT_PRONOUN_DICTIONARY = "com.aliasi.ne.EnglishPronounDictionary";

	/**
	 * Name of parameter for input directory.
	 */
	static final String INPUT_DIRECTORY_PARAM = "inputDir";

	/**
	 * Name of parameter for output directory.
	 */
	static final String OUTPUT_DIRECTORY_PARAM = "outputDir";

	/**
	 * Name of parameter for comma-separated value of file suffixes to annotate.
	 */
	static final String FILE_EXTENSIONS_PARAM = "fileSuffixes";

}
