<?php

namespace WooseopKim\Ludite;

use Closure;
use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LuaEngine;
use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LuaModule;
use MediaWiki\Extension\Scribunto\Scribunto;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Parser;
use MediaWiki\Title\Title;
use ParserOptions;

define('MEDIAWIKI', true);
define('MW_ENTRY_POINT', 'cli');

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../vendor/mediawiki/core/includes/BootstrapHelperFunctions.php';
require_once __DIR__.'/../vendor/mediawiki/core/includes/Setup.php';

class LuaModuleRenderer
{
    private string $lang;

    private Closure $callback;

    public function __construct(string $lang, callable $callback)
    {
        $this->lang = $lang;
        $this->callback = Closure::fromCallable($callback);
    }

    /** @param array<mixed> $args */
    public function render(string $moduleName, string $funcName, array $args): ?string
    {
        $services = MediaWikiServices::getInstance();
        $parser = $services->getParserFactory()->create();
        $options = ParserOptions::newFromAnon();
        $options->setTemplateCallback($this->callback);
        $options->setTargetLanguage($services->getLanguageFactory()->getLanguage($this->lang));
        $parser->startExternalParse(Title::newMainPage(), $options, Parser::OT_HTML, true);
        /** @var LuaEngine */
        $engine = Scribunto::newDefaultEngine(['parser' => $parser]);
        $pp = $parser->getPreprocessor();
        $frame = $pp->newFrame();

        $title = Title::makeTitle(NS_MODULE, $moduleName);
        /** @var LuaModule */
        $module = $engine->fetchModuleFromParser($title);
        if ($module == null) {
            return null;
        }
        $func = $engine->executeModule($module->getInitChunk(), $funcName, $frame);
        [$result] = $engine->getInterpreter()->callFunction($func, ...$args);

        return $result;
    }
}
