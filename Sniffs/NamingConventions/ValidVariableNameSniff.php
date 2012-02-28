<?php
/**
 * Copyright (c) 2008-2011 Andreas Heigl<andreas@heigl.org>
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Andreas Heigl <andreas@heigl.org>
 * @copyright 2008-2012 Andreas Heigl
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @link      http://pear.heigl.org/package/phpcs
 * @link      http://github.com/heiglandreas/phpcs
 */

if (class_exists('PHP_CodeSniffer_Standards_AbstractVariableSniff', true) === false) {
    throw new PHP_CodeSniffer_Exception('Class PHP_CodeSniffer_Standards_AbstractVariableSniff not found');
}

/**
 * Squiz_Sniffs_NamingConventions_ValidVariableNameSniff.
 *
 * Checks the naming of variables and member variables.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Andreas Heigl <andreas@heigl.org>
 * @copyright 2008-2012 Andreas Heigl
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   Release: 1.1
 * @link      http://pear.heigl.org/package/phpcs
 * @link      http://github.com/heiglandreas/phpcs
 */
class HeiglOrg_Sniffs_NamingConventions_ValidVariableNameSniff extends PHP_CodeSniffer_Standards_AbstractVariableSniff
{

    /**
     * Tokens to ignore so that we can find a DOUBLE_COLON.
     *
     * @var array
     */
    private $_ignore = array(
                        T_WHITESPACE,
                        T_COMMENT,
                       );


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    protected function processVariable(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens  = $phpcsFile->getTokens();
        $varName = ltrim($tokens[$stackPtr]['content'], '$');

        $phpReservedVars = array(
                            '_SERVER',
                            '_GET',
                            '_POST',
                            '_REQUEST',
                            '_SESSION',
                            '_ENV',
                            '_COOKIE',
                            '_FILES',
                            'GLOBALS',
                           );

        // If it's a php reserved var, then its ok.
        if (in_array($varName, $phpReservedVars) === true) {
            return;
        }

        $objOperator = $phpcsFile->findNext(array(T_WHITESPACE), ($stackPtr + 1), null, true);
        if ($tokens[$objOperator]['code'] === T_OBJECT_OPERATOR) {
            // Check to see if we are using a variable from an object.
            $var = $phpcsFile->findNext(array(T_WHITESPACE), ($objOperator + 1), null, true);
            if ($tokens[$var]['code'] === T_STRING) {
                // Either a var name or a function call, so check for bracket.
                $bracket = $phpcsFile->findNext(array(T_WHITESPACE), ($var + 1), null, true);

                if ($tokens[$bracket]['code'] !== T_OPEN_PARENTHESIS) {
                    $objVarName = $tokens[$var]['content'];

                    // There is no way for us to know if the var is public or private,
                    // so we have to ignore a leading underscore if there is one and just
                    // check the main part of the variable name.
                    $originalVarName = $objVarName;
                    if (substr($objVarName, 0, 1) === '_') {
                        $objVarName = substr($objVarName, 1);
                    }

                    if (PHP_CodeSniffer::isCamelCaps($objVarName, false, true, false) === false) {
                        $error = 'Variable "%s" is not in valid camel caps format';
                        $data  = array($originalVarName);
                        $phpcsFile->addError($error, $var, 'NotCamelCaps', $data);
                    } else if (preg_match('|\d|', $objVarName)) {
                        $warning = 'Variable "%s" contains numbers but this is discouraged';
                        $data    = array($originalVarName);
                        $phpcsFile->addWarning($warning, $stackPtr, 'ContainsNumbers', $data);
                    }
                }//end if
            }//end if
        }//end if

        // There is no way for us to know if the var is public or private,
        // so we have to ignore a leading underscore if there is one and just
        // check the main part of the variable name.
        $originalVarName = $varName;
        if (substr($varName, 0, 1) === '_') {
            $objOperator = $phpcsFile->findPrevious(array(T_WHITESPACE), ($stackPtr - 1), null, true);
            if ($tokens[$objOperator]['code'] === T_DOUBLE_COLON) {
                // The variable lives within a class, and is referenced like
                // this: MyClass::$_variable, so we don't know its scope.
                $inClass = true;
            } else {
                $inClass = $phpcsFile->hasCondition($stackPtr, array(T_CLASS, T_INTERFACE));
            }

            if ($inClass === true) {
                $varName = substr($varName, 1);
            }
        }

        if (PHP_CodeSniffer::isCamelCaps($varName, false, true, false) === false) {
            $error = 'Variable "%s" is not in valid camel caps format';
            $data  = array($originalVarName);
            $phpcsFile->addError($error, $stackPtr, 'NotCamelCaps', $data);
        } else if (preg_match('|\d|', $varName)) {
            $warning = 'Variable "%s" contains numbers but this is discouraged';
            $data    = array($originalVarName);
            $phpcsFile->addWarning($warning, $stackPtr, 'ContainsNumbers', $data);
        }

    }//end processVariable()


    /**
     * Processes class member variables.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    protected function processMemberVar(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens      = $phpcsFile->getTokens();
        $varName     = ltrim($tokens[$stackPtr]['content'], '$');
        $memberProps = $phpcsFile->getMemberProperties($stackPtr);
        $public      = ($memberProps['scope'] === 'public');

        if ($public === true) {
            if (substr($varName, 0, 1) === '_') {
                $error = 'Public member variable "%s" must not contain a leading underscore';
                $data  = array($varName);
                $phpcsFile->addError($error, $stackPtr, 'PublicHasUnderscore', $data);
                return;
            }
        } else {
            if (substr($varName, 0, 1) === '_') {
                $scope = ucfirst($memberProps['scope']);
                $error = '%s member variable "%s" shall not contain a leading underscore';
                $data  = array(
                          $scope,
                          $varName,
                         );
                $phpcsFile->addWarning($error, $stackPtr, 'PrivateHasUnderscore', $data);
                //return;
            }
        } //*/

        if (self::isCamelCaps($varName) === false) {
            $error = 'Variable "%s" is not in valid camel caps format';
            $data  = array($varName);
            $phpcsFile->addError($error, $stackPtr, 'MemberVarNotCamelCaps', $data);
        } else if (preg_match('|\d|', $varName)) {
            $warning = 'Variable "%s" contains numbers but this is discouraged';
            $data    = array($varName);
            $phpcsFile->addWarning($warning, $stackPtr, 'MemberVarContainsNumbers', $data);
        }

    }//end processMemberVar()


    /**
     * Processes the variable found within a double quoted string.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the double quoted
     *                                        string.
     *
     * @return void
     */
    protected function processVariableInString(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $phpReservedVars = array(
                            '_SERVER',
                            '_GET',
                            '_POST',
                            '_REQUEST',
                            '_SESSION',
                            '_ENV',
                            '_COOKIE',
                            '_FILES',
                            'GLOBALS',
                           );

        if (preg_match_all('|[^\\\]\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)|', $tokens[$stackPtr]['content'], $matches) !== 0) {
            foreach ($matches[1] as $varName) {
                // If it's a php reserved var, then its ok.
                if (in_array($varName, $phpReservedVars) === true) {
                    continue;
                }

                // There is no way for us to know if the var is public or private,
                // so we have to ignore a leading underscore if there is one and just
                // check the main part of the variable name.
                $originalVarName = $varName;
                if (substr($varName, 0, 1) === '_') {
                    if ($phpcsFile->hasCondition($stackPtr, array(T_CLASS, T_INTERFACE)) === true) {
                        $varName = substr($varName, 1);
                    }
                }

                if (PHP_CodeSniffer::isCamelCaps($varName, false, true, false) === false) {
                    $varName = $matches[0];
                    $error   = 'Variable "%s" is not in valid camel caps format';
                    $data    = array($originalVarName);
                    $phpcsFile->addError($error, $stackPtr, 'StringVarNotCamelCaps', $data);
                } else if (preg_match('|\d|', $varName)) {
                    $warning = 'Variable "%s" contains numbers but this is discouraged';
                    $data    = array($originalVarName);
                    $phpcsFile->addWarning($warning, $stackPtr, 'StringVarContainsNumbers', $data);
                }
            }
        }//end if

    }//end processVariableInString()

    public static function hasInvalidChars($string)
    {
        $legalChars = 'a-zA-Z0-9_';
        if ( preg_match("|[^$legalChars]|", $string) > 0 ) {
            return true;
        }
        return false;
    }

    public static function isCamelCaps($string)
    {
        $length = strlen($string);
        $lastCharWasCaps = false;

        for ( $i = 0; $i < $length; $i++ ) {
            $isCaps = false;
            if ( strtoupper($string{$i}) === $string{$i} ) {
                $isCaps = true;
            }
            if ( $isCaps === true && $lastCharWasCaps === true ) {
                return false;
            }
            $lastCharWasCaps = $isCaps; 
        }
        return true;
    }

}//end class

?>
