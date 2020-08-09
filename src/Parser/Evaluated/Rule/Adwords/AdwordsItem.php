<?php
/**
 * @license see LICENSE
 */

namespace Serps\SearchEngine\Google\Parser\Evaluated\Rule\Adwords;

use Serps\Core\Serp\BaseResult;
use Serps\Core\Serp\IndexedResultSet;
use Serps\SearchEngine\Google\AdwordsResultType;
use Serps\SearchEngine\Google\Exception\InvalidDOMException;
use Serps\SearchEngine\Google\Page\GoogleDom;
use Serps\SearchEngine\Google\Parser\ParsingRuleInterface;

class AdwordsItem implements ParsingRuleInterface
{

    public function match(GoogleDom $dom, \Serps\Core\Dom\DomElement $node)
    {
        if ($node->getAttribute('class') == 'ads-fr') {
            return self::RULE_MATCH_MATCHED;
        }
        return self::RULE_MATCH_NOMATCH;
    }
    public function parse(GoogleDom $googleDOM, \DomElement $node, IndexedResultSet $resultSet)
    {
        $item = [
            'title' => function () use ($googleDOM, $node) {
                $aTag = $googleDOM->getXpath()->query('descendant::h3/a[2]', $node)->item(0);
                if (!$aTag) {
                    $aTag = $googleDOM->cssQuery('a div', $node)->item(0);
                    if (!$aTag) {
                        return null;
                    }
                }
                return $aTag->nodeValue;
            },
            'url' => function () use ($node, $googleDOM) {
                $aTag = $googleDOM->getXpath()->query('descendant::h3/a[2]', $node)->item(0); // TODO remove
                if (!$aTag) {
                    $aTag = $googleDOM->cssQuery('a', $node)->item(0);
                    if (!$aTag) {
                        throw new InvalidDOMException('Cannot find ads anchor');
                    }
                }

                return $googleDOM->getUrl()->resolveAsString($aTag->getAttribute('href'));
            },
            'visurl' => function () use ($node, $googleDOM) {
                $tag = $googleDOM->cssQuery('a div', $node)->item(1);
                if (!$tag) {
                    return null;
                }

                $tag = $googleDOM->cssQuery('div span', $tag)->item(2);
                if (!$tag) {
                    return null;
                }
                return $tag->nodeValue;
            },
            'description' => function () use ($node, $googleDOM) {
                $tag = $googleDOM->cssQuery('a', $node)->item(0)->parentNode->nextSibling;
                if (!$tag) {
                    return null;
                }
                return $tag->nodeValue;
            },
        ];

        $resultSet->addItem(new BaseResult(AdwordsResultType::AD, $item));
    }
}
