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

import com.aliasi.ne.Decoder;

import com.aliasi.ne.FilterTagParser;
import com.aliasi.ne.FilterForSpecifiedTypes;
import com.aliasi.ne.NEAnnotateFilter;
import com.aliasi.ne.NEDictionary;
import com.aliasi.ne.NEScorer;
import com.aliasi.ne.StopList;
import com.aliasi.ne.NETrain;
import com.aliasi.ne.TagParser;
import com.aliasi.ne.Tags;

import com.aliasi.tokenizer.TokenizerFactory;
import com.aliasi.tokenizer.TokenCategorizer;

import com.aliasi.sentences.SentenceAnnotateFilter;

import com.aliasi.util.FilterStringArray;
import com.aliasi.util.AbstractCommand;
import com.aliasi.util.Arrays;
import com.aliasi.util.Reflection;
import com.aliasi.util.Streams;
import com.aliasi.util.Strings;

import java.io.BufferedInputStream;
import java.io.BufferedOutputStream;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.InputStream;
import java.io.IOException;
import java.io.OutputStream;
import java.io.OutputStreamWriter;
import java.io.PrintWriter;
import java.io.UnsupportedEncodingException;

import java.net.MalformedURLException;
import java.net.URL;

import java.util.Properties;

import org.xml.sax.InputSource;

/**
 * @author  Bob Carpenter
 * @version 2.0
 * @since   LingPipe1.0
 */
public abstract class AbstractEntityCmd extends AbstractCommand {

    /**
     * Construct an instance of an abstract named-entity
     * command.
     *
     * @param args Args to supply to abstract command.
     */
    public AbstractEntityCmd(String[] args) {
        super(args,DEFAULT_PROPERTIES);
    }

    /**
     * Returns <code>true</code> if the all caps flag is set
     * and all data should be capitalized before further processing.
     *
     * @return <code>true</code> if the all caps flag is set.
     */
    public boolean getAllCaps() {
        boolean isAllCaps = hasFlag(ALL_CAPS_PARAM);
        System.out.println("All Caps Data=" + isAllCaps);
        return isAllCaps;
    }

    /**
     * Returns the stop list specified for this abstract command, or
     * <code>null</code> if none is specified.
     *
     * @return The stop list specified for this abstract command, or
     * <code>null</code> if none is specified.
     */
    public StopList getStopList() {
        String stopListFileName = getArgument(STOP_LIST_PARAM);
        if (stopListFileName == null) return null;
        File stopListFile = new File(stopListFileName);
        if (!stopListFile.isFile()) {
            illegalArgument("Stop list must be an ordinary file.",
                            STOP_LIST_PARAM);
        }
        String charset = getArgument(STOP_LIST_CHARSET_PARAM);
        StopList stopList = new StopList();
        try {
            stopList.readEntries(stopListFile,charset,
                                 getTokenizerFactory());
        } catch (IOException e) {
            illegalArgument("Exception reading stop list from file="
                            + stopListFile
                            + " with charset=" + charset
                            + ". Exception=" + e);
        }
        return stopList;
    }

    /**
     * Returns the pronoun dictionary associated with this abstract
     * coreference command, or <code>null</code> if none is specified.
     *
     * @return Pronoun dictionary associated with this abstract
     * named-entity command.
     * @throws IllegalArgumentException If there is an exception
     * constructing the pronoun dictionary from the command line.
     */
    public NEDictionary getPronounDictionary() {
        return getDictionaryByParameter(PRONOUN_DICTIONARY_PARAM);
    }

    /**
     * Returns the user-defined dictionary associated with this
     * abstract coreference command, or <code>null</code>
     * if none is specified.
     *
     * @return User-defined dictionary associated with this abstract
     * named-entity command.
     * @throws IllegalArgumentException If there is an exception
     * constructing the pronoun dictionary from the command line.
     */
    public NEDictionary getUserDictionary() {
        NEDictionary dict = null;
        dict = getDictionaryByParameter(USER_DICTIONARY_PARAM);
        if (dict == null){
            String dictCharSet = getArgument(USER_DICTIONARY_CHARSET_PARAM);
            System.out.println("Got dict charset of "+dictCharSet);
            dict = getDictionaryByFileParameter(USER_DICTIONARY_FILE_PARAM,
                                                getTokenizerFactory(),
                                                dictCharSet);
        }
        return dict;
    }

    /**
     * Returns the dictionary whose class is picked out by name
     * by the specified parameter.  Returns <code>null</code> if
     * the parameter is not specified.
     *
     * @param parameterName Name of parameter specifying dictionary
     * class name.
     * @return Dictionary picked out by class specified by parameter
     * name.
     * @throws IllegalArgumentException If there is an exception
     * constructing the pronoun dictionary from the command line.
     */
    private NEDictionary getDictionaryByParameter(String parameterName) {
        String className
            = getArgument(parameterName);
        if (className == null) {
            System.out.println("No dictionary supplied for param="
                               + parameterName);
            return null;
        }
        System.out.println("Dictionary for " + parameterName
                           + "=" + className);
        NEDictionary dictionary = null;
        try {
            dictionary
                = (NEDictionary)
                Reflection.newInstance(className);
        } catch (ClassCastException e) {
            illegalPropertyArgument("Exception casting to class="
                                    + "com.aliasi.ne.NEDictionary",
                                    parameterName);
        } catch (IllegalArgumentException e) {
            illegalPropertyArgument("Exception creating dictionary=" + e.getMessage(),
                                    parameterName);
        }
        if (dictionary == null)
            illegalPropertyArgument("Could not construct dictionary.",
                                    parameterName);
        return dictionary;
    }


    /**
     * Returns a dictionary based on the indicated file.
     * Returns <code>null</code> if
     * the parameter is not specified.
     *
     * @param dictionaryName Name of parameter specifying dictionary.
     * @return Dictionary created by parameter
     * name.
     * @throws IllegalArgumentException If there is an exception
     * constructing the pronoun dictionary from the command line.
     */
    private NEDictionary getDictionaryByFileParameter(String parameterName,
                                                      TokenizerFactory tokFact,
                                                      String charset) {
        NEDictionary dictionary = null;
        String fileName
            = getArgument(parameterName);
        if (fileName == null) {
            System.out.println("No dictionary supplied for param="
                               + parameterName);
            return null;
        }
        try {
            File file = new File(fileName);
            System.out.println("Dictionary for " + parameterName
                               + "=" + fileName);
            dictionary
                = new NEDictionary(tokFact,
                                   file,
                                   charset);
            if (dictionary == null)
                illegalPropertyArgument("Could not construct dictionary.",
                                        parameterName);
        }
        catch (IOException e) {
            System.out.println("Unable to load userdictionary from file");
        }
        return dictionary;
    }


    /**
     * Returns the specified or the default named-entity
     * annotation filter.
     *
     * @throws IllegalArgumentException If there is an exception
     * constructing the annotation filter from the command-line
     * arguments.
     */
    public NEAnnotateFilter getAnnotationFilter(boolean sentencesOnly) {

        Decoder decoder = getDecoder();
        TokenizerFactory tokenizerFactory = getTokenizerFactory();

        String neFilterClassName
            = getExistingArgument(NE_ANNOTATE_FILTER_PARAM);
        System.out.println("Annotation Filter=" + neFilterClassName);

        Object[] consArgs;
        Class[] consClasses;
        String[] elements = null;
        if (sentencesOnly) {
            elements = SENTENCE_ELEMENTS;
        } else if (hasArgument(ELEMENTS_PARAM)) {
            elements = getCSV(ELEMENTS_PARAM);
        }
        if (elements != null) {
            System.out.println("Elements to annotate for NE=" +
                               Arrays.arrayToString(elements));
            // annotate specified elements
            consArgs = new Object[] {
                decoder,
                tokenizerFactory,
                elements
            };
            consClasses =
                new Class[] {
                    Decoder.class,
                    TokenizerFactory.class,
                    String[].class
                };
        } else {
            System.out.println("Annotating all elements.");
            consArgs = new Object[] {
                decoder,
                tokenizerFactory,
            };
            consClasses =
                new Class[] {
                    Decoder.class,
                    TokenizerFactory.class,
                };
        }
        NEAnnotateFilter neFilter = null;
        try {
            neFilter = (NEAnnotateFilter)
                Reflection.newInstance(neFilterClassName,
                                       consArgs,
                                       consClasses);
        } catch (ClassCastException e) {
            illegalPropertyArgument("neAnnotateFilter must extend class="
                                    + DEFAULT_NE_ANNOTATE_FILTER,
                                    NE_ANNOTATE_FILTER_PARAM);
        } catch (IllegalArgumentException e) {
            illegalPropertyArgument("Illegal construction=" + e.getMessage(),
                                    NE_ANNOTATE_FILTER_PARAM);
        }
        if (neFilter == null)
            illegalPropertyArgument("Could not construct neFilter.",
                                    NE_ANNOTATE_FILTER_PARAM);

        NEDictionary pronounDictionary = getPronounDictionary();
        if (pronounDictionary != null)
            neFilter.setPronounDictionary(pronounDictionary);

        NEDictionary userDictionary = getUserDictionary();
        if (userDictionary != null)
            neFilter.setUserDictionary(userDictionary);

        StopList stopList = getStopList();
        if (stopList != null)
            neFilter.setStopList(stopList);

        if (hasFlag(NE_CLOSURE_FLAG)) {
            System.out.println("Applying NE Closure");
            neFilter.setClosure(true);
        } else {
            neFilter.setClosure(false);
        }

        return neFilter;
    }

    /**
     * Returns the named-entity scorer for this command.
     *
     * @return Namend-entity scorer for this command.
     * @throws IllegalArgumentException If the scorer could not be
     * constructed.
     */
    public NEScorer getNEScorer() {
        NEScorer scorer = null;
        try {
            String neScorerClassName
                = getExistingArgument(NE_SCORER_PARAM);
            scorer = (NEScorer) Reflection.newInstance(neScorerClassName);
            scorer.ignorePrefix("the");
            scorer.ignorePrefix("The");
        } catch (ClassCastException e) {
            illegalPropertyArgument("neScorer must extend class="
                                    + DEFAULT_NE_SCORER,
                                    NE_SCORER_PARAM);
        } catch (IllegalArgumentException e) {
            illegalPropertyArgument("Could not construct scorer error="
                                    + e.getMessage(),
                                    NE_SCORER_PARAM);
        }
        if (scorer == null)
            illegalPropertyArgument("Could not construct neScorer.",
                                    NE_SCORER_PARAM);
        return scorer;
    }

    public File getDictionaryFile() {
        if (!hasArgument(DICTIONARY_FILE_PARAM)) {
            System.out.println("No dictionary specified.");
            return null;
        }
        File file = getArgumentFile(DICTIONARY_FILE_PARAM);
        if (!file.isFile())
            illegalPropertyArgument("Must be ordinary file.",
                                    DICTIONARY_FILE_PARAM);
        return file;
    }


    /**
     * Returns the content character set of the input specified by
     * the command line arguments.
     *
     * @return Content character set specified on the command line, or
     * <code>null</code> if none is specified.
     * @throws IllegalArgumentException If the content type parameter
     * is not specified.
     */
    protected String getContentCharset() {
        String contentType = getExistingArgument(CONTENT_TYPE_PARAM);
        int charsetIndex = contentType.indexOf("charset=");
        if (charsetIndex < 0) {
            System.out.println("No content character set specified");
            System.out.print("Using platform default charset=");
            System.out.println(Streams.getDefaultJavaCharset());
            return null;
        }
        String charsetName = contentType.substring(charsetIndex
                                                   + "charset=".length());
        System.out.println("Input character set=" + charsetName);
        return charsetName;
    }


    /**
     * Returns the content type of input specified by the command line
     * arguments.
     *
     * @return String representing content type.
     * @throws IllegalArgumentException If the specified content type
     * is not well formed.
     */
    protected String getContentType() {
        String contentType = getExistingArgument(CONTENT_TYPE_PARAM);
        System.out.println("Content Type=" + contentType);
        if (contentType.startsWith(XML_INPUT_TYPE))
            return XML_INPUT_TYPE;
        if (contentType.startsWith(HTML_INPUT_TYPE))
            return HTML_INPUT_TYPE;
        if (contentType.startsWith(PLAIN_INPUT_TYPE))
            return PLAIN_INPUT_TYPE;
        illegalPropertyArgument("If specified, must be one of: "
                                + "text/xml, text/html, text/plain",
                                CONTENT_TYPE_PARAM);
        return null;
    }

    /**
     * Returns the value of the named-entity pruning threshold.
     * This is the negative natural log of the fraction of the
     * best score's probability estimate required to maintain a
     * hypothesis.
     *
     * @return Value of the named-entity pruning threshold.
     */
    public double getNEPruningThreshold() {
        double pruningThreshold
            = getArgumentDouble(NE_PRUNING_THRESHOLD_PARAM);
        System.out.println("Pruning Threshold=" + pruningThreshold);
        return pruningThreshold;
    }

    /**
     * Returns the decoder specified by the command-line
     * arguments.
     *
     * @return Decoder specified by command line.
     * @throws IllegalArgumentException If there is an exception
     * getting the model file or token categorizer, or there is an
     * exception constructing the decoder.
     */
    public Decoder getDecoder() {
        Decoder decoder = null;
        try {
            System.out.println("Creating Decoder.  May take awhile.");
            decoder = new Decoder(getModelFile(),
                                  getTokenCategorizer(),
                                  getNEPruningThreshold());
        } catch (IOException e) {
            illegalArgument("Exception constructing decoder.",e);
        }
        System.out.println("Created Decoder.");
        return decoder;
    }

    public int getKnownMinTokenCount() {
        int min = getArgumentInt(KNOWN_MIN_TOKEN_COUNT_PARAM);
        System.out.println("Known minimum token count=" + min);
        return min;
    }

    public double getLambdaFactor() {
        double lambdaFactor = getArgumentDouble(LAMBDA_FACTOR_PARAM);
        System.out.println("Lambda Factor=" + lambdaFactor);
        return lambdaFactor;
    }

    public double getLogUniformVocabEstimate() {
        double uniformEstimate
            = getArgumentDouble(LOG_UNIFORM_VOCABULARY_ESTIMATE_PARAM);
        System.out.println("Uniform Log Vocab Estimate=" + uniformEstimate);
        return uniformEstimate;
    }

    /**
     * Returns the model file specified by the command line.
     *
     * @return Normal file containing the model.
     * @throws IllegalArgumentException If the model is not specified
     * on the command line or is not a creatable file.
     */
    protected File getModelFile() {
        File modelFile = getArgumentCreatableFile(MODEL_FILE_PARAM);
        System.out.println("modelFile=" + modelFile);
        return modelFile;
    }

    public NETrain getNETrain() {
        TokenCategorizer categorizer = getTokenCategorizer();
        TokenizerFactory tokenizerFactory = getTokenizerFactory();
        NETrain trainer = new NETrain(categorizer,tokenizerFactory);
        File file = getDictionaryFile();
        if (file != null)
            trainer.setDictionaryFile(file);
        trainer.setKnownMinTokenCount(getKnownMinTokenCount());
        trainer.setPruneTokenMinimimum(getPruneTokenMinimum());
        trainer.setPruneTagMinimum(getPruneTagMinimum());
        trainer.setSmoothTagCount(getSmoothTagCount());
        trainer.setLambdaFactor(getLambdaFactor());
        trainer.setLogUniformVocabularyEstimate(getLogUniformVocabEstimate());
        trainer.setAllCaps(getAllCaps());
        return trainer;
    }

    public String getOutputCharset() {
        String charset = getArgument(OUTPUT_CHARSET_PARAM);
        if (charset != null) {
            System.out.println("User specified output charset=" + charset);
        } else {
            charset = Streams.getDefaultJavaCharset();
            System.out.println("Using default Java Output Charset for Locale=" + charset);
        }
        return charset;
    }

    /**
     * Returns the output stream specified by the command line
     * arguments.
     *
     * @return The output stream specified by the arguments.
     * @throws IllegalArgumentException If the file specified by the
     * output does not exist and cannot be created.
     */
    protected OutputStream getOutputStream() {
        if (!hasArgument(OUTPUT_FILE_PARAM)) {
            System.out.println("Output to System.out");
            return new BufferedOutputStream(System.out);
        }
        FileOutputStream fileOut = null;
        try {
            File file = getArgumentFile(OUTPUT_FILE_PARAM);
            System.out.println("Output to file=" + file);
            fileOut = new FileOutputStream(file);
        } catch (FileNotFoundException e) {
            illegalPropertyArgument("File not found.",OUTPUT_FILE_PARAM);
        }
        return new BufferedOutputStream(fileOut);
    }

    protected PrintWriter getOutputPrintWriter() {
        OutputStream out = getOutputStream();
        String charset = getOutputCharset();
        PrintWriter printer = null;
        try {
            printer = new PrintWriter(new OutputStreamWriter(out,charset));
        } catch (UnsupportedEncodingException e) {
            illegalPropertyArgument("Illegal character set=" + charset,
                                    OUTPUT_CHARSET_PARAM);
        }
        return printer;
    }

    public int getPruneTagMinimum() {
        int min = getArgumentInt(PRUNE_TAG_MINIMUM_PARAM);
        System.out.println("Prune tag minimum param=" + min);
        return min;
    }

    public int getSmoothTagCount() {
        int count = getArgumentInt(SMOOTH_TAG_COUNT_PARAM);
        System.out.println("Smooth tag count=" + count);
        return count;
    }

    public int getPruneTokenMinimum() {
        int min = getArgumentInt(PRUNE_TOKEN_MINIMUM_PARAM);
        System.out.println("Prune token minimum=" + min);
        return min;
    }

    protected File getKeyFile() {
        File keyFile = getArgumentFile(KEY_FILE_PARAM);
        System.out.println("Key file=" + keyFile);
        return keyFile;
    }

    protected File getResponseFile() {
        File responseFile = getArgumentFile(RESPONSE_FILE_PARAM);
        System.out.println("Response file=" + responseFile);
        return responseFile;
    }

    /**
     * Returns a newly constructed tag parser as specified by the
     * command-line arguments.
     *
     * @return Tag parser specified by the arguments.
     * @throws IllegalArgumentException If there is an exception
     * retrieving the tag parser from the command-line arguments.
     */
    protected TagParser getTagParser() {
        String tagParserClassName = getExistingArgument(TAG_PARSER_PARAM);
        System.out.println("Tag Parser=" + tagParserClassName);
        TagParser result = null;
        TagParser coreParser = null;
        try {
            coreParser = (TagParser) Reflection.newInstance(tagParserClassName);
        } catch (ClassCastException e) {
            illegalPropertyArgument("Error casting to class="
                                    + "com.aliasi.train.TagParser",
                                    TAG_PARSER_PARAM);
        } catch (IllegalArgumentException e) {
            illegalPropertyArgument("Error constructing tag parser=" + e.getMessage(),
                                    TAG_PARSER_PARAM);
        }
        if (coreParser == null){
            illegalPropertyArgument("Error constructing tag parser.",
                                    TAG_PARSER_PARAM);
        }
        if (hasTypesToKeep()) { //there is type filtering
            String[] typesToScore = getCSV(TYPES_TO_KEEP_PARAM);
            FilterForSpecifiedTypes typeFilter = new FilterForSpecifiedTypes(typesToScore);
            result =
                new FilterTagParser(coreParser,
                                    FilterStringArray.NO_OP_FILTER_STRING_ARRAY,
                                    typeFilter);
        }
        else {
            result = coreParser;
        }
        return result;
    }

    protected double getTestFraction() {
        double testFraction = getArgumentDouble(TEST_FRACTION_PARAM);
        System.out.println("Test Fraction=" + testFraction);
        return testFraction;
    }

    /**
     * Return the token categorizer specified by the command-line
     * arguments.
     *
     * @return The token categorizer specified by these arguments.
     * @throws IllegalArgumentException If there is an exception
     * constructing or casting the tokenizer factory.
     */
    public TokenCategorizer getTokenCategorizer() {
        String tokenCategorizerClassName
            = getExistingArgument(TOKEN_CATEGORIZER_PARAM);
        System.out.println("Token categorizer=" + tokenCategorizerClassName);
        TokenCategorizer categorizer = null;
        try {
            categorizer = (TokenCategorizer)
                Reflection.newInstance(tokenCategorizerClassName);
        } catch (ClassCastException e) {
            illegalPropertyArgument("Exception casting to class="
                                    + "com.aliasi.tokenizer.TokenCategorizer.",
                                    TOKEN_CATEGORIZER_PARAM);
        } catch (IllegalArgumentException e) {
            illegalPropertyArgument("Exception constructing categorizier=" + e.getMessage(),
                                    TOKEN_CATEGORIZER_PARAM);
        }
        if (categorizer == null)
            illegalPropertyArgument("Could not construct token categorizer",
                                    TOKEN_CATEGORIZER_PARAM);
        return categorizer;
    }

    /**
     * Return the tokenizer factory specified by the command-line
     * arguments.
     *
     * @return The tokenizer factory specified by these arguments.
     * @throws IllegalArgumentException If there is an exception
     * constructing or casting the tokenizer factory.
     */
    protected TokenizerFactory getTokenizerFactory() {

        String tokenizerFactoryClassName
            = getExistingArgument(TOKENIZER_FACTORY_PARAM);
        System.out.println("tokenizerFactory.class="
                           + tokenizerFactoryClassName);
        TokenizerFactory factory = null;
        try {
            factory = (TokenizerFactory)
                Reflection.newInstance(tokenizerFactoryClassName);
        } catch (ClassCastException e) {
            illegalPropertyArgument("Exception casting from tokenizer factory class="
                                    + tokenizerFactoryClassName
                                    + " to com.aliasi.tokenizer.TokenizerFactory",
                                    TOKENIZER_FACTORY_PARAM);
        } catch (IllegalArgumentException e) {
            illegalPropertyArgument("Exceptiong creating tokenizer factory="
                                    + e.getMessage(),
                                    TOKENIZER_FACTORY_PARAM);
        }
        return factory; // will be non-null if returned
    }

    protected boolean printAlignment() {
        return hasFlag(PRINT_ALIGNMENT_FLAG);
    }

    protected boolean printElidedAlignment() {
        return hasFlag(PRINT_ELIDED_ALIGNMENT_FLAG);
    }

    protected boolean printExactScores() {
        return hasFlag(PRINT_EXACT_SCORES_FLAG);
    }

    protected boolean hasTypesToKeep() {
        return hasArgument(TYPES_TO_KEEP_PARAM);
    }


    protected boolean printPartialScores() {
        return hasFlag(PRINT_PARTIAL_SCORES_FLAG);
    }

    protected static final String TYPES_TO_KEEP_PARAM = "typesToKeep";
    private static final String ALL_CAPS_PARAM = "allCaps";
    protected static final String CONTENT_TYPE_PARAM = "contentType";
    private static final String DICTIONARY_FILE_PARAM = "dictionary";
    private static final String INPUT_SOURCE_PARAM = "inputSource";
    private static final String KEY_FILE_PARAM = "keyFile";
    protected static final String KEY_DIR_PARAM = "keyDir";
    protected static final String SCORES_DIR_PARAM = "scoresDir";
    private static final String KNOWN_MIN_TOKEN_COUNT_PARAM
        = "knownMinTokenCount";
    private static final String LAMBDA_FACTOR_PARAM = "lambdaFactor";
    private static final String LOG_UNIFORM_VOCABULARY_ESTIMATE_PARAM
        = "logUniformVocabularyEstimate";
    private static final String MODEL_FILE_PARAM = "model";
    private static final String NE_ANNOTATE_FILTER_PARAM = "neAnnotateFilter";
    protected static final String NE_CLOSURE_FLAG = "neClosure";
    private static final String NE_PRUNING_THRESHOLD_PARAM
        = "nePruningThreshold";
    private static final String NE_SCORER_PARAM = "neScorer";
    private static final String OUTPUT_FILE_PARAM = "outputFile";
    private static final String OUTPUT_CHARSET_PARAM = "outputCharset";
    private static final String PRINT_ALIGNMENT_FLAG = "printAlignment";
    private static final String PRINT_ELIDED_ALIGNMENT_FLAG
        = "printElidedAlignment";
    private static final String PRINT_EXACT_SCORES_FLAG
        = "printExactScores";
    private static final String PRINT_PARTIAL_SCORES_FLAG
        = "printPartialScores";
    protected static String PRONOUN_DICTIONARY_PARAM = "pronounDictionary";
    private static final String PRUNE_TAG_MINIMUM_PARAM = "pruneTagMin";
    private static final String PRUNE_TOKEN_MINIMUM_PARAM = "pruneTokenMin";
    private static final String RESPONSE_FILE_PARAM = "responseFile";
    protected static final String RESPONSE_DIR_PARAM = "responseDir";
    private static final String SMOOTH_TAG_COUNT_PARAM = "smoothTagCount";
    private static final String STOP_LIST_PARAM = "stopList";
    private static final String STOP_LIST_CHARSET_PARAM = "stopListCharset";
    private static final String TAG_PARSER_PARAM = "tagParser";
    private static final String TEST_FRACTION_PARAM = "testFraction";
    private static final String TOKEN_CATEGORIZER_PARAM = "tokenCategorizer";
    private static final String TOKENIZER_FACTORY_PARAM = "tokenizerFactory";
    private static final String USER_DICTIONARY_PARAM = "userDictionary";
    private static final String USER_DICTIONARY_FILE_PARAM = "userDictionaryFile";
    private static final String USER_DICTIONARY_CHARSET_PARAM = "userDictionaryCharset";

    private static final String DEFAULT_KNOWN_MIN_TOKEN_COUNT
        = Integer.toString(8);
    private static final String DEFAULT_LAMBDA_FACTOR
        = Double.toString(4.0);
    private static final String DEFAULT_LOG_UNIFORM_VOCABULARY_ESTIMATE
        = Double.toString(Math.log(1.0/1000000.0));
    private static final String DEFAULT_NE_ANNOTATE_FILTER
        = "com.aliasi.ne.NEAnnotateFilter";
    private static final String DEFAULT_NE_PRUNING_THRESHOLD = "8.0";
    private static final String DEFAULT_NE_SCORER
        = "com.aliasi.ne.NEScorer";
    private static final String DEFAULT_OUTPUT_CHARSET = Strings.UTF8;
    private static final String DEFAULT_PRUNE_TAG_MINIMUM
        = Integer.toString(1);
    private static final String DEFAULT_PRUNE_TOKEN_MINIMUM
        = Integer.toString(1);
    private static final String DEFAULT_SMOOTH_TAG_COUNT
        = Integer.toString(1); // makes all transitions legal
    private static final String DEFAULT_STOP_LIST_CHARSET
        = Strings.UTF8;
    private static final String DEFAULT_TEST_FRACTION
        = Double.toString(0.10);
    private static final String DEFAULT_TAG_PARSER
        = "com.aliasi.corpus.parsers.TagParserMUC";
    private static final String DEFAULT_TOKEN_CATEGORIZER
        = "com.aliasi.tokenizer.IndoEuropeanTokenCategorizer";
    private static final String DEFAULT_TOKENIZER_FACTORY
        = "com.aliasi.tokenizer.IndoEuropeanTokenizerFactory";


    /**
     * String representing text/xml MIME type.
     */
    protected static String XML_INPUT_TYPE = "text/xml";

    /**
     * String representing text/html MIME type.
     */
    protected static String HTML_INPUT_TYPE = "text/html";

    /**
     * String representing text/plain MIME type.
     */
    protected static String PLAIN_INPUT_TYPE= "text/plain";

    /**
     * Element to use as root element for plain text output
     */
    protected static String PLAIN_TEXT_TOP_LEVEL_ELEMENT = "plainText";

    /**
     * Element used to specify which elements get annotated.  There
     * is no default value.
     */
    protected static String ELEMENTS_PARAM = "elements";

    /**
     * An array consisisting of just the sentence delimeter element
     * <code>com.aliasi.sentences.SentenceAnnotateFilter.SENTENCE_ELEMENTS</code>.
     */
    protected static String[] SENTENCE_ELEMENTS = new String[] {
        SentenceAnnotateFilter.SENTENCE_ELEMENT
    };

    /**
     * Default values of command-line properties.
     */
    protected static Properties DEFAULT_PROPERTIES = new Properties();
    static {
        DEFAULT_PROPERTIES.setProperty(CONTENT_TYPE_PARAM,
                                       XML_INPUT_TYPE);
        DEFAULT_PROPERTIES.setProperty(KNOWN_MIN_TOKEN_COUNT_PARAM,
                                       DEFAULT_KNOWN_MIN_TOKEN_COUNT);
        DEFAULT_PROPERTIES.setProperty(LAMBDA_FACTOR_PARAM,
                                       DEFAULT_LAMBDA_FACTOR);
        DEFAULT_PROPERTIES.setProperty(LOG_UNIFORM_VOCABULARY_ESTIMATE_PARAM,
                                       DEFAULT_LOG_UNIFORM_VOCABULARY_ESTIMATE);
        DEFAULT_PROPERTIES.setProperty(NE_ANNOTATE_FILTER_PARAM,
                                       DEFAULT_NE_ANNOTATE_FILTER);
        DEFAULT_PROPERTIES.setProperty(NE_PRUNING_THRESHOLD_PARAM,
                                       DEFAULT_NE_PRUNING_THRESHOLD);
        DEFAULT_PROPERTIES.setProperty(NE_SCORER_PARAM,
                                       DEFAULT_NE_SCORER);
        DEFAULT_PROPERTIES.setProperty(OUTPUT_CHARSET_PARAM,
                                       DEFAULT_OUTPUT_CHARSET);
        DEFAULT_PROPERTIES.setProperty(PRUNE_TAG_MINIMUM_PARAM,
                                       DEFAULT_PRUNE_TAG_MINIMUM);
        DEFAULT_PROPERTIES.setProperty(PRUNE_TOKEN_MINIMUM_PARAM,
                                       DEFAULT_PRUNE_TOKEN_MINIMUM);
        DEFAULT_PROPERTIES.setProperty(STOP_LIST_CHARSET_PARAM,
                                       DEFAULT_STOP_LIST_CHARSET);
        DEFAULT_PROPERTIES.setProperty(TAG_PARSER_PARAM,
                                       DEFAULT_TAG_PARSER);
        DEFAULT_PROPERTIES.setProperty(TEST_FRACTION_PARAM,
                                       DEFAULT_TEST_FRACTION);
        DEFAULT_PROPERTIES.setProperty(TOKEN_CATEGORIZER_PARAM,
                                       DEFAULT_TOKEN_CATEGORIZER);
        DEFAULT_PROPERTIES.setProperty(TOKENIZER_FACTORY_PARAM,
                                       DEFAULT_TOKENIZER_FACTORY);
        DEFAULT_PROPERTIES.setProperty(SMOOTH_TAG_COUNT_PARAM,
                                       DEFAULT_SMOOTH_TAG_COUNT);
    }

}
