<?php

/*
+---------------------------------------------------------------------------+
| Revive Adserver                                                           |
| http://www.revive-adserver.com                                            |
|                                                                           |
| Copyright: See the COPYRIGHT.txt file.                                    |
| License: GPLv2 or later, see the LICENSE.txt file.                        |
+---------------------------------------------------------------------------+
*/

/**
 * Guards against reintroducing calls to the global each() function, which
 * was removed in PHP 8.0, in files that have been migrated away from it.
 * Commented-out code is scanned too, so it stays PHP 8 safe if restored.
 *
 * @package    Max
 * @subpackage TestSuite
 */
class Test_GlobalEach extends UnitTestCase
{
    public const FILES = [
        'lib/max/Delivery/adSelect.php',
        'lib/max/Delivery/common.php',
        'lib/max/tests/unit/AdminDA.dal.test.php',
        'www/admin/lib-gui.inc.php',
        'www/delivery_dev/ck.php',
        'www/delivery_dev/cl.php',
    ];

    public function testFilesDoNotCallGlobalEach()
    {
        foreach (self::FILES as $file) {
            $code = file_get_contents(MAX_PATH . '/' . $file);
            $this->assertIsA($code, 'string', "Unable to read {$file}");

            $aLines = $this->_findGlobalEachCalls((string) $code);
            $this->assertEqual([], $aLines, "{$file} calls each() on line(s) " . implode(', ', $aLines));
        }
    }

    public function testDetector()
    {
        $code = <<<'CODE'
            <?php
            list($k, $v) = each($a);
            [$k, $v] = \each($a);
            $o->each($a);
            $o?->each($a);
            Foo::each($a);
            function each(&$a) {}
            $each = 1;
            // [$k, $v] = each($a);
            /*
                $x = 1;
                list($k, $v) = EACH($a);
            */
            CODE;

        $this->assertEqual([2, 3, 9, 12], $this->_findGlobalEachCalls($code));
    }

    /**
     * @return int[] Line numbers of global each() calls, including in comments.
     */
    private function _findGlobalEachCalls(string $code, int $lineOffset = 0): array
    {
        $aLines = [];
        $aTokens = array_values(array_filter(
            token_get_all($code),
            fn($token) => !is_array($token) || $token[0] !== T_WHITESPACE,
        ));

        foreach ($aTokens as $i => $token) {
            if (!is_array($token)) {
                continue;
            }

            [$id, $text, $line] = $token;

            if ($id === T_COMMENT || $id === T_DOC_COMMENT) {
                $body = preg_replace('#^(/\*+|//|\#)|\*/$#', '', $text);
                $aLines = array_merge($aLines, $this->_findGlobalEachCalls('<?php ' . $body, $lineOffset + $line - 1));
                continue;
            }

            $isEachName = ($id === T_STRING && strtolower($text) === 'each')
                || ($id === T_NAME_FULLY_QUALIFIED && strtolower($text) === '\each');
            if (!$isEachName || ($aTokens[$i + 1] ?? null) !== '(') {
                continue;
            }

            $previous = $aTokens[$i - 1] ?? null;
            if (is_array($previous) && in_array($previous[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION], true)) {
                continue;
            }

            $aLines[] = $lineOffset + $line;
        }

        return $aLines;
    }
}
