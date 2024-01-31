<?php

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use PHPUnit\Framework\TestSuite;

/**
 * Zend Framework
 * LICENSE
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Markup
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (https://www.zend.com)
 * @license    https://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

if (!defined("PHPUnit_MAIN_METHOD")) {
    define("PHPUnit_MAIN_METHOD", "Zend_Markup_BbcodeAndHtmlTest::main");
}

require_once 'Zend/Markup.php';
require_once 'Zend/Filter/StringToUpper.php';

/**
 * @category   Zend
 * @package    Zend_Markup
 * @subpackage UnitTests
 * @group      Zend_Markup
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (https://www.zend.com)
 * @license    https://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Markup_BbcodeAndHtmlTest extends TestCase
{
    protected Zend_Markup_Renderer_RendererAbstract $_markup;

    public static function main(): void
    {
        $suite = new TestSuite("Zend_Markup_MarkupTest");
        (new resources_Runner())->run($suite);
    }

    protected function set_up(): void
    {
        $this->_markup = Zend_Markup::factory('bbcode', 'html');
    }

    protected function tear_down(): void
    {
        unset($this->_markup);
    }

    public function testBasicTags(): void
    {
        $this->assertSame('<strong>foo</strong>bar', $this->_markup->render('[b]foo[/b]bar'));
        $this->assertSame(
            '<strong>foo<em>bar</em>foo</strong>ba[r',
            $this->_markup->render('[b=test file="test"]foo[i hell=nice]bar[/i]foo[/b]ba[r')
        );
    }

    public function testComplicatedTags(): void
    {
        $this->assertSame(
            '<a href="https://framework.zend.com/">https://framework.zend.com/</a>',
            $this->_markup->render('[url]https://framework.zend.com/[/url]')
        );

        $this->assertSame(
            '<a href="https://framework.zend.com/">foo</a>',
            $this->_markup->render('[url=https://framework.zend.com/]foo[/url]')
        );

        $this->assertSame('bar', $this->_markup->render('[url="javascript:alert(1)"]bar[/url]'));

        $this->assertSame(
            '<img src="https://framework.zend.com/images/logo.png" alt="logo" />',
            $this->_markup->render('[img]https://framework.zend.com/images/logo.png[/img]')
        );

        $this->assertSame(
            '<img src="https://framework.zend.com/images/logo.png" alt="Zend Framework" />',
            $this->_markup->render('[img alt="Zend Framework"]https://framework.zend.com/images/logo.png[/img]')
        );
    }

    public function testExceptionParserWrongInputType(): void
    {
        $this->expectException('Zend_Markup_Parser_Exception');

        $this->_markup->getParser()->parse([]);
    }

    public function testExceptionParserEmptyInput(): void
    {
        $this->expectException('Zend_Markup_Parser_Exception');

        $this->_markup->getParser()->parse('');
    }

    /**
     * @throws Zend_Markup_Renderer_Exception
     * @throws Zend_Loader_PluginLoader_Exception
     */
    public function testAddTags(): void
    {
        $this->_markup->getPluginLoader()->addPrefixPath(
            'Zend_Markup_Test_Renderer_Html',
            'Zend/Markup/Test/Renderer/Html'
        );

        $this->_markup->addMarkup(
            'bar',
            Zend_Markup_Renderer_RendererAbstract::TYPE_CALLBACK,
            ['group' => 'inline']
        );

        $this->_markup->addMarkup(
            'suppp',
            Zend_Markup_Renderer_RendererAbstract::TYPE_REPLACE,
            ['start' => '<sup>', 'end' => '</sup>', 'group' => 'inline']
        );

        $this->_markup->addMarkup(
            'zend',
            Zend_Markup_Renderer_RendererAbstract::TYPE_REPLACE,
            ['replace' => 'Zend Framework', 'group' => 'inline', 'empty' => true]
        );

        $this->_markup->addMarkup(
            'line',
            Zend_Markup_Renderer_RendererAbstract::TYPE_ALIAS,
            ['name' => 'hr']
        );

        $this->assertSame(
            '[foo=blaat]hell<sup>test</sup>blaat[/foo]',
            $this->_markup->render('[bar="blaat"]hell[suppp]test[/suppp]blaat[/]')
        );

        $this->assertSame('Zend Framework', $this->_markup->render('[zend]'));
        $this->assertSame('<hr />', $this->_markup->render('[line]'));
        $this->assertSame('<sup>test aap</sup>test', $this->_markup->render('[suppp]test aap[/suppp]test'));
    }

    public function testHtmlUrlTitleIsRenderedCorrectly(): void
    {
        $this->assertSame(
            '<a href="https://exampl.com" title="foo">test</a>',
            $this->_markup->render('[url=https://exampl.com title=foo]test[/url]')
        );
    }

    public function testValueLessAttributeDoesNotThrowNotice(): void
    {
        // Notice: Uninitialized string offset: 42
        // in Zend/Markup/Parser/Bbcode.php on line 316
        $expected = '<a href="https://example.com">Example</a>';
        $value = '[url=https://example.com foo]Example[/url]';
        $this->assertSame($expected, $this->_markup->render($value));
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testAttributeNotEndingValueDoesNotThrowNotice(): void
    {
        // Notice: Uninitialized string offset: 13
        // in Zend/Markup/Parser/Bbcode.php on line 337

        $this->_markup->render('[url=https://framework.zend.com/ title="');
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testAttributeFollowingValueDoesNotThrowNotice(): void
    {
        // Notice: Uninitialized string offset: 38
        // in Zend/Markup/Parser/Bbcode.php on line 337

        $this->_markup->render('[url="https://framework.zend.com/"title');
    }

    public function testHrTagWorks(): void
    {
        $this->assertSame('foo<hr />bar', $this->_markup->render('foo[hr]bar'));
    }

    public function testFunkyCombos(): void
    {
        $expected = '<span style="text-decoration: underline;">a[/b][hr]b'
                    . '<strong>c</strong></span><strong>d</strong>[/u]e';
        $outcome = $this->_markup->render('[u]a[/b][hr]b[b]c[/u]d[/b][/u]e');
        $this->assertSame($expected, $outcome);
    }

    public function testImgSrcsConstraints(): void
    {
        $this->assertSame('F/\!ZLrFz', $this->_markup->render('F[img]/\!ZLrFz[/img]'));
    }

    public function testColorConstraintsAndJs(): void
    {
        $input = "<kokx> i think you mean? [color=\"onclick='foobar();'\"]your text[/color] DASPRiD";
        $expected = "&lt;kokx&gt; i think you mean? <span>your text</span> DASPRiD";
        $this->assertSame($expected, $this->_markup->render($input));
    }

    public function testNeverEndingAttribute(): void
    {
        $input = "[color=\"green]your text[/color]";
        $expected = '<span>your text</span>';
        $this->assertSame($expected, $this->_markup->render($input));
    }

    public function testTreatmentNonTags(): void
    {
        $input = '[span][acronym][h1][h2][h3][h4][h5][h6][nothing]'
                 . '[/h6][/h5][/h4][/h3][/h2][/h1][/acronym][/span]';
        $expected = '<span><acronym><h1><h2><h3><h4><h5><h6>[nothing]'
                    . '</h6></h5></h4></h3></h2></h1></acronym></span>';
        $this->assertSame($expected, $this->_markup->render($input));
    }

    public function testListItems(): void
    {
        $input = "[list][*]Foo*bar (item 1)\n[*]Item 2\n[*]Trimmed (Item 3)\n[/list]";
        $expected = "<ul><li>Foo*bar (item 1)</li><li>Item 2</li><li>Trimmed (Item 3)</li></ul>";
        $this->assertSame($expected, $this->_markup->render($input));

        $this->assertSame('<ul><li>blaat</li></ul>', $this->_markup->render('[list][*]blaat[/*][/list]'));
    }

    public function testListDisallowingPlaintext(): void
    {
        $input = "[list]\ntest[*]Foo[/*]\n[/list]";
        $expected = "<ul><li>Foo</li></ul>";
        $this->assertSame($expected, $this->_markup->render($input));
    }

    public function testFailureAfterCodeTag(): void
    {
        $input = "[code][b][/code][list][*]Foo[/*][/list]";
        $expected = highlight_string('[b]', true) . '<ul><li>Foo</li></ul>';
        $this->assertSame($expected, $this->_markup->render($input));
    }

    public function testInvalidationAfterInvalidTag(): void
    {
        $input = "[b][list][*]Foo[/*][/list][/b]";
        $expected = "<strong>[list][*]Foo[/*][/list]</strong>";
        $this->assertSame($expected, $this->_markup->render($input));
    }

    public function testListTypes(): void
    {
        $types = [
            '01' => 'decimal-leading-zero',
            '1' => 'decimal',
            'i' => 'lower-roman',
            'I' => 'upper-roman',
            'a' => 'lower-alpha',
            'A' => 'upper-alpha',
            'alpha' => 'lower-greek',
        ];

        foreach ($types as $type => $style) {
            $input = "[list=$type][*]Foobar\n[*]Zend\n[/list]";
            $expected = "<ol style=\"list-style-type: $style\"><li>Foobar</li><li>Zend</li></ol>";
            $this->assertSame($expected, $this->_markup->render($input));
        }
    }

    public function testHtmlTags(): void
    {
        $m = $this->_markup;

        $this->assertSame('<strong>foo</strong>', $m->render('[b]foo[/b]'));
        $this->assertSame('<span style="text-decoration: underline;">foo</span>', $m->render('[u]foo[/u]'));
        $this->assertSame('<em>foo</em>', $m->render('[i]foo[/i]'));
        $this->assertSame('<cite>foo</cite>', $m->render('[cite]foo[/cite]'));
        $this->assertSame('<del>foo</del>', $m->render('[del]foo[/del]'));
        $this->assertSame('<ins>foo</ins>', $m->render('[ins]foo[/ins]'));
        $this->assertSame('<sub>foo</sub>', $m->render('[sub]foo[/sub]'));
        $this->assertSame('<span>foo</span>', $m->render('[span]foo[/span]'));
        $this->assertSame('<acronym>foo</acronym>', $m->render('[acronym]foo[/acronym]'));
        $this->assertSame('<h1>F</h1>', $m->render('[h1]F[/h1]'));
        $this->assertSame('<h2>R</h2>', $m->render('[h2]R[/h2]'));
        $this->assertSame('<h3>E</h3>', $m->render('[h3]E[/h3]'));
        $this->assertSame('<h4>E</h4>', $m->render('[h4]E[/h4]'));
        $this->assertSame('<h5>A</h5>', $m->render('[h5]A[/h5]'));
        $this->assertSame('<h6>Q</h6>', $m->render('[h6]Q[/h6]'));
        $this->assertSame('<span style="color: red;">foo</span>', $m->render('[color=red]foo[/color]'));
        $this->assertSame('<span style="color: #00FF00;">foo</span>', $m->render('[color=#00FF00]foo[/color]'));
        $this->assertSame(highlight_string("<?php\nexit;", true), $m->render("[code]<?php\nexit;[/code]"));
        $this->assertSame('<p>I</p>', $m->render('[p]I[/p]'));
        $this->assertSame('N', $m->render('[ignore]N[/ignore]'));
        $this->assertSame('<blockquote>M</blockquote>', $m->render('[quote]M[/quote]'));
        $this->assertSame('<hr />foo<hr />bar[/hr]', $m->render('[hr]foo[hr]bar[/hr]'));
    }

    public function testWrongNesting(): void
    {
        $this->assertSame(
            '<strong>foo<em>bar</em></strong>',
            $this->_markup->render('[b]foo[i]bar[/b][/i]')
        );
        $this->assertSame(
            '<strong>foo<em>bar</em></strong><em>kokx</em>',
            $this->_markup->render('[b]foo[i]bar[/b]kokx[/i]')
        );
    }

    public function testHtmlAliases(): void
    {
        $m = $this->_markup;

        $this->assertSame($m->render('[b]F[/b]'), $m->render('[bold]F[/bold]'));
        $this->assertSame($m->render('[bold]R[/bold]'), $m->render('[strong]R[/strong]'));
        $this->assertSame($m->render('[i]E[/i]'), $m->render('[i]E[/i]'));
        $this->assertSame($m->render('[i]E[/i]'), $m->render('[italic]E[/italic]'));
        $this->assertSame($m->render('[i]A[/i]'), $m->render('[emphasized]A[/emphasized]'));
        $this->assertSame($m->render('[i]Q[/i]'), $m->render('[em]Q[/em]'));
        $this->assertSame($m->render('[u]I[/u]'), $m->render('[underline]I[/underline]'));
        $this->assertSame($m->render('[cite]N[/cite]'), $m->render('[citation]N[/citation]'));
        $this->assertSame($m->render('[del]G[/del]'), $m->render('[deleted]G[/deleted]'));
        $this->assertSame($m->render('[ins]M[/ins]'), $m->render('[insert]M[/insert]'));
        $this->assertSame($m->render('[s]E[/s]'), $m->render('[strike]E[/strike]'));
        $this->assertSame($m->render('[sub]-[/sub]'), $m->render('[subscript]-[/subscript]'));
        $this->assertSame($m->render('[sup]D[/sup]'), $m->render('[superscript]D[/superscript]'));
        $this->assertSame($m->render('[url]google.com[/url]'), $m->render('[a]google.com[/a]'));
        $this->assertSame(
            $m->render('[img]https://google.com/favicon.ico[/img]'),
            $m->render('[image]https://google.com/favicon.ico[/image]')
        );
    }

    public function testEmptyTagName(): void
    {
        $this->assertSame('[]', $this->_markup->render('[]'));
    }

    public function testStyleAlignCombination(): void
    {
        $m = $this->_markup;
        $this->assertSame(
            '<h1 style="color: green;text-align: left;">Foobar</h1>',
            $m->render('[h1 style="color: green" align=left]Foobar[/h1]')
        );
        $this->assertSame(
            '<h1 style="color: green;text-align: center;">Foobar</h1>',
            $m->render('[h1 style="color: green;" align=center]Foobar[/h1]')
        );
    }

    public function testXssInAttributeValues(): void
    {
        $m = $this->_markup;
        $this->assertSame(
            '<strong class="&quot;&gt;xss">foobar</strong>',
            $m->render('[b class=\'">xss\']foobar[/b]')
        );
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testWrongNestedLists(): void
    {
        $m = $this->_markup;
        // thanks to PadraicB for finding this
        $input = <<<BBCODE
[list]
[*] Subject 1
[list]
[*] First
[*] Second
[/list]
[*] Subject 2
[/list]
BBCODE;
        $m->render($input);
    }

    public function testAttributeWithoutValue(): void
    {
        $m = $this->_markup;

        $this->assertSame('<strong>foobar</strong>', $m->render('[b=]foobar[/b]'));
    }

    public function testRemoveTag(): void
    {
        $this->_markup->removeMarkup('b');

        $this->assertSame('[b]bar[/b]', $this->_markup->render('[b]bar[/b]'));
    }

    public function testClearTags(): void
    {
        $this->_markup->clearMarkups();

        $this->assertSame('[i]foo[/i]', $this->_markup->render('[i]foo[/i]'));
    }

    public function testAddFilters(): void
    {
        $m = $this->_markup;

        $m->addDefaultFilter(new Zend_Filter_StringToUpper());

        $this->assertSame('<strong>HELLO</strong>', $m->render('[b]hello[/b]'));
    }

    /**
     * @throws Zend_Markup_Renderer_Exception
     */
    public function testProvideFilterChainToTag(): void
    {
        $m = $this->_markup;

        $filter = new Zend_Filter_HtmlEntities();

        $this->_markup->addMarkup(
            'suppp',
            Zend_Markup_Renderer_RendererAbstract::TYPE_REPLACE,
            ['start' => '<sup>', 'end' => '</sup>', 'group' => 'inline', 'filter' => $filter]
        );
        $this->assertSame(
            "filter<br />\n<sup>filter\n&amp;\nfilter</sup>",
            $m->render("filter\n[suppp]filter\n&\nfilter[/suppp]")
        );
    }

    public function testSetFilterForExistingMarkup(): void
    {
        $m = $this->_markup;

        $filter = new Zend_Filter_StringToUpper();

        $m->setFilter($filter, 'strong');

        $this->assertSame('<strong>FOO&BAR</strong>baz', $m->render('[b]foo&bar[/b]baz'));
    }

    public function testAddFilterForExistingMarkup(): void
    {
        $m = $this->_markup;

        $filter = new Zend_Filter_StringToUpper();

        $m->addFilter($filter, 'i', Zend_Filter::CHAIN_PREPEND);

        $this->assertSame('<em>FOO&amp;BAR</em>baz', $m->render('[i]foo&bar[/i]baz'));
    }

    public function testValidUri(): void
    {
        $this->assertTrue(Zend_Markup_Renderer_Html::isValidUri("https://www.example.com"));
        $this->assertNotTrue(Zend_Markup_Renderer_Html::isValidUri("www.example.com"));
        $this->assertNotTrue(Zend_Markup_Renderer_Html::isValidUri("https:///test"));
        $this->assertTrue(Zend_Markup_Renderer_Html::isValidUri("https://www.example.com"));
        $this->assertTrue(Zend_Markup_Renderer_Html::isValidUri("magnet:?xt=urn:bitprint:XZBS763P4HBFYVEMU5OXQ44XK32OMLIN.HGX3CO3BVF5AG2G34MVO3OHQLRSUF4VJXQNLQ7A &xt=urn:ed2khash:aa52fb210465bddd679d6853b491ccce&"));
        $this->assertNotTrue(Zend_Markup_Renderer_Html::isValidUri("javascript:alert(1)"));
    }

    public function testXssInImgAndUrl(): void
    {
        $this->assertSame(
            '<a href="https://google.com/&quot;&lt;script&gt;alert(1)&lt;/script&gt;">...</a>',
            $this->_markup->render('[url=\'https://google.com/"<script>alert(1)</script>\']...[/url]')
        );
        $this->assertSame(
            '<img src="https://google.com/&amp;quot;&amp;lt;script&amp;gt;alert(1)&amp;lt;/script&amp;gt;" alt="/script&amp;gt;" />',
            $this->_markup->render('[img]https://google.com/"<script>alert(1)</script>[/img]')
        );
    }

    /**
     * @throws Zend_Markup_Renderer_Exception
     */
    public function testAddGroup(): void
    {
        $m = $this->_markup;

        $m->addGroup('table', ['block']);
        $m->addGroup('table-row', ['table']);
        $m->addGroup('table-cell', ['table-row'], ['inline', 'inline-empty']);

        $m->addMarkup(
            'table',
            Zend_Markup_Renderer_RendererAbstract::TYPE_REPLACE,
            [
                'tag' => 'table',
                'group' => 'table',
            ]
        );
        $m->addMarkup(
            'tr',
            Zend_Markup_Renderer_RendererAbstract::TYPE_REPLACE,
            [
                'tag' => 'tr',
                'group' => 'table-row',
            ]
        );
        $m->addMarkup(
            'td',
            Zend_Markup_Renderer_RendererAbstract::TYPE_REPLACE,
            [
                'tag' => 'td',
                'group' => 'table-cell',
            ]
        );

        $this->assertSame(
            '<table><tr><td>test</td></tr></table>',
            $m->render('[table][tr][td]test[/td][/tr][/table]')
        );
    }

    /**
     * Test for ZF-9220
     */
    public function testUrlMatchCorrectly(): void
    {
        $m = $this->_markup;

        $this->assertSame(
            '<a href="https://framework.zend.com/">test</a><a href="https://framework.zend.com/">test</a>',
            $m->render('[url="https://framework.zend.com/"]test[/url][url="https://framework.zend.com/"]test[/url]')
        );
    }

    /**
     * Test for ZF-9463
     */
    public function testNoXssInH(): void
    {
        $m = $this->_markup;
        $this->assertSame(
            '<h1>&lt;script&gt;alert(&quot;hi&quot;);&lt;/script&gt;</h1>',
            $m->render('[h1]<script>alert("hi");</script>[/h1]')
        );
    }
}

// Call Zend_Markup_BbcodeAndHtmlTest::main()
// if this source file is executed directly.
if (PHPUnit_MAIN_METHOD === "Zend_Markup_BbcodeAndHtmlTest::main") {
    Zend_Markup_BbcodeAndHtmlTest::main();
}
