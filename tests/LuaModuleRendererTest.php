<?php

namespace WooseopKim\Ludite;

use MediaWiki\Parser\Parser;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class LuaModuleRendererTest extends TestCase
{
    public function testRenderSimpleModule(): void
    {
        $lang = 'en';
        $callback = /** @return array<string, array<array<string, Title>>|Title|string> */ function (Title $title, Parser $parser): array {
            if ($title->getFullText() != 'Module:arithmetic') {
                throw new UnexpectedValueException("expected Module:arithmetic but got {$title->getFullText()} instead");
            }

            return [
                'text' => '
                    return {
                        add = function (x, y)
                            return x + y
                        end
                    }
                ',
                'finalTitle' => $title,
                'deps' => [],
            ];
        };
        $renderer = new LuaModuleRenderer($lang, $callback);

        $result = $renderer->render('arithmetic', 'add', [13, 17]);

        $this->assertSame($result, '30');
    }
}
