<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Tests\Unit\Sparql;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Thomas Maroschik <tmaroschik@dfau.de>
 *  All rights reserved
 *
 *  This class is a port of the corresponding class of the
 *  {@link http://aksw.org/Projects/Erfurt Erfurt} project.
 *  All credits go to the Erfurt team.
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Erfurt Sparql Query - little test script
 *
 * @package    query
 * @author     Jonas Brekle <jonas.brekle@gmail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version    $Id$
 */
class Query2Test extends \Erfurt\Tests\Unit\BaseTestCase {

	protected $query;

	public function setUp() {
		$this->query = new \Erfurt\Sparql\Query2();
	}

	/**
	 * what should a unconfigured query evaluate to?
	 * to a empty string?
	 * to a query that matches nothing (FILTER(false))?
	 * or to a query that matches all triples (?s ?p ?o)?
	 *
	 * for now it is:
	SELECT *
	WHERE {
	}
	 * this is checked here
	 */
	public function testInit() {
		$this->query = new \Erfurt\Sparql\Query2();

		$this->assertEquals(
			preg_replace("/\s\s/", " ", (string)$this->query),
			"SELECT * WHERE { } ");
	}

	/**
	 * copy&pasted from the old "test"-script
	 * no real unit test, just a use lot of classes so errors reveal themselves :)
	 */
	public function old1() {

		try {
			//test graph pattern
			$query = new \Erfurt\Sparql\Query2();
			$pattern = new \Erfurt\Sparql\Query2\GroupGraphPattern();
			$s = new \Erfurt\Sparql\Query2\Variable('s');
			$foafPrefix = new \Erfurt\Sparql\Query2\Prefix('foaf', new \Erfurt\Sparql\Query2\IriRef('http://xmlns.com/foaf/0.1/'));
			$triple1 = new \Erfurt\Sparql\Query2\Triple($s, new \Erfurt\Sparql\Query2\A(), new \Erfurt\Sparql\Query2\IriRef('Person', $foafPrefix));
			$iri1 = new \Erfurt\Sparql\Query2\IriRef('http://bob-home.example.com');
			$iri2 = new \Erfurt\Sparql\Query2\IriRef('http://bob-work.example.com');
			$iri3 = new \Erfurt\Sparql\Query2\IriRef('http://bob-work.example.com/mailaddr_checker_func');
			$query->addPrefix($foafPrefix);
			$query->addFrom('http://3ba.se/conferences/', true); //we can add strings - will be converted internally
			$query->addFrom('http://3ba.se/conferences/'); //doubled
			$query->removeFrom(1); //so remove
			$prefixedUri1 = new \Erfurt\Sparql\Query2\IriRef('name', $foafPrefix);
			$prefixedUri2 = new \Erfurt\Sparql\Query2\IriRef('website', $foafPrefix);
			$name = new \Erfurt\Sparql\Query2\RDFLiteral('bob', 'en');
			$bnode = new \Erfurt\Sparql\Query2\BlankNode('bn');
			$collecion = new \Erfurt\Sparql\Query2\Collection(array($s, $bnode));

			$propList = new \Erfurt\Sparql\Query2\PropertyList(
				array(
					 array(
						 'verb' => $prefixedUri1,
						 'objList' =>
						 new \Erfurt\Sparql\Query2\ObjectList(
							 array($name)
						 )
					 ),
					 array(
						 'verb' => $prefixedUri2,
						 'objList' =>
						 new \Erfurt\Sparql\Query2\ObjectList(
							 array(
								  $iri1,
								  $iri2
							 )
						 )
					 )
				)
			);
			$bnPropList = new \Erfurt\Sparql\Query2\BlankNodePropertyList($propList);
			$triplesamesubj = new \Erfurt\Sparql\Query2\TriplesSameSubject(
				$collecion,
				$propList
			);
			$optional_pattern = new \Erfurt\Sparql\Query2\OptionalGraphPattern();
			$optional_pattern2 = new \Erfurt\Sparql\Query2\OptionalGraphPattern();
			$mbox = new \Erfurt\Sparql\Query2\Variable('mbox');
			$mbox2 = new \Erfurt\Sparql\Query2\Variable('mbox');
			$triple2 = new \Erfurt\Sparql\Query2\Triple($s, new \Erfurt\Sparql\Query2\IriRef('mbox', $foafPrefix), $mbox);

			//test filter
			$or = new \Erfurt\Sparql\Query2\ConditionalOrExpression();
			$one1 = new \Erfurt\Sparql\Query2\NumericLiteral(1);
			$one2 = new \Erfurt\Sparql\Query2\RDFLiteral('1', 'int');

			$st = new \Erfurt\Sparql\Query2\sameTerm($one1, $one2);
			$additiv = new \Erfurt\Sparql\Query2\AdditiveExpression();
			$additiv->setElements(
				array(
					 array(
						 "op" => \Erfurt\Sparql\Query2\AdditiveExpression::invOperator,
						 "exp" => $one1
					 ),
					 array(
						 "op" => \Erfurt\Sparql\Query2\AdditiveExpression::operator,
						 "exp" => $one2)
				)
			);

			$nst = new \Erfurt\Sparql\Query2\UnaryExpressionNot($st);
			$and = new \Erfurt\Sparql\Query2\ConditionalAndExpression();
			$regex = new \Erfurt\Sparql\Query2\Regex(new \Erfurt\Sparql\Query2\Str($mbox), new \Erfurt\Sparql\Query2\RDFLiteral('/home/'), new \Erfurt\Sparql\Query2\RDFLiteral('i'));
			$filter = new \Erfurt\Sparql\Query2\Filter($or);

			//build structure
			$query->setWhere(
				$pattern
						->addElement($triple1)
						->addElement($triplesamesubj)
						->addElement($triplesamesubj) //duplicate
						->addElement(
					$optional_pattern
							->addElement($triple2)
				)
						->addElement($filter
											 ->setConstraint($or
																	 ->addElement($and
																						  ->addElement($nst)
																						  ->addElement($additiv)
																						  ->addElement(new \Erfurt\Sparql\Query2\isLiteral($mbox))
																						  ->addElement(new \Erfurt\Sparql\Query2\UserFunction($iri3, array($mbox)))
															 )
																	 ->addElement($regex)
									 )
				)
			);
			$query->optimize();
			$nst->remove($query);
			// or
			// $and->removeElement($nst->getID());
			// but the 2nd command removes only occurences of $nst in add, while $nst->remove() removes all ocurrences

			//modify query
			$query->addProjectionVar($mbox);
			$query->setCountStar(true);

			//$query->setReduced(true);
			$query->setDistinct(true);

			$query->setLimit(50);
			$query->setOffset(30);
			$idx = $query->getOrder()->add($mbox);
			//$query->getOrder()->toggleDirection($idx);

			//test different types
			//$query->setQueryType(\Erfurt\Sparql\Query2::typeConstruct);
			//$query->getWhere()->removeAllElements();
			//$query->getConstructTemplate()->addElement(new \Erfurt\Sparql\Query2\Triple($s, $prefixedUri1, $name));

			//echo $query->getSparql();

			$usagebefore = memory_get_usage();
			//$query2 = new \Erfurt\Sparql\Query2();
			//for($i=0;$i<1000; $i++){
			//    ${"x".$i} = "new";
			//}
			$x = "new";
			$usageafter = memory_get_usage();
			echo "used " . ($usageafter - $usagebefore) . " bytes for 1 new var";
		}
		catch (Exception $e) {
			throw $e;
			$this->assertTrue(false);
		}
	}


	public function testOld2() {
		function microtime_float() {
			list($usec, $sec) = explode(" ", microtime());
			return ((float)$usec + (float)$sec);
		}

		try {
			$timesum = (float)0;
			$memorysum = (float)0;
			for ($i = 0; $i < 1; $i++) {
				$usagebefore = memory_get_usage(true);
				$timebefore = microtime_float();

				//test graph pattern
				$query = new \Erfurt\Sparql\Query2();
				$iri3 = new \Erfurt\Sparql\Query2\IriRef('http://example.com/');
				$exPrefix = new \Erfurt\Sparql\Query2\Prefix('abc', $iri3);
				$prefixedUri1 = new \Erfurt\Sparql\Query2\IriRef('xyz', $exPrefix);
				$var = new \Erfurt\Sparql\Query2\Variable('var');
				$triple = new \Erfurt\Sparql\Query2\Triple($var, new \Erfurt\Sparql\Query2\Variable("p"), new \Erfurt\Sparql\Query2\Variable("o"));

				// Query building
				$query->setBase(new \Erfurt\Sparql\Query2\IriRef('http://base.com'));
				$query->addPrefix($exPrefix);
				$query->addFrom($prefixedUri1);
				$query->addProjectionVar($var);
				$query->setLimit(10);
				$query->setOffset(20);
				$query->getOrder()->add($var);
				$query->addElement($triple);

				$timeafter = microtime_float();
				$timediff = $timeafter - $timebefore;
				$usageafter = memory_get_usage(true);
				$usagediff = $usageafter - $usagebefore;

				$memorysum += $usagediff;
				$timesum += $timediff;
				//echo "used " . ($usageafter - $usagebefore) . " bytes and " . $timediff . " sec.";
				//echo $usagediff."\n";
				//echo "<pre>"; var_dump($query); echo "</pre>";
			}
			//echo "$i used avg ".($memorysum/$i) . " bytes and avg " .(number_format($timesum/$i, 9))." seconds";
		}
		catch (Exception $e) {
			throw $e;
			$this->assertTrue(false);
		}
	}

	public function testProjectionVars() {
		$var = new \Erfurt\Sparql\Query2\Variable('s');
		$this->assertFalse($this->query->hasProjectionVars());
		$this->query->addProjectionVar($var);
		$this->assertTrue($this->query->hasProjectionVars());
		$this->assertContains($var, $this->query->getProjectionVars());
		$vars = $this->query->getProjectionVars();
		$this->assertTrue(count($vars) == 1);
		$this->assertEquals('s', $vars[0]->getName());

		$this->query->removeProjectionVar($var);
		$vars = $this->query->getProjectionVars();
		$this->assertTrue(empty($vars));

		$this->query->addProjectionVar($var);
		$this->query->removeAllProjectionVars();
		$vars = $this->query->getProjectionVars();
		$this->assertTrue(empty($vars));
	}

	public function testFroms() {
		$from = new \Erfurt\Sparql\Query2\GraphClause("http://test.com");
		$this->query->addFrom($from);
		$this->assertTrue($this->query->hasFroms());
		$this->assertContains($from, $this->query->getFroms());

		$froms = $this->query->getFroms();
		$this->assertTrue(count($froms) == 1);
		$this->assertEquals($from, $froms[0]);

		$this->query->removeFroms();
		$froms = $this->query->getFroms();
		$this->assertTrue(empty($froms));

		$this->query->addFrom($from);
		$this->query->removeFrom(0);
		$froms = $this->query->getFroms();
		$this->assertTrue(empty($froms));
	}

	public function testPrefixes() {
		$prefix = new \Erfurt\Sparql\Query2\Prefix("pre", "http://test.com");
		$this->query->addPrefix($prefix);
		$this->assertTrue($this->query->hasPrefix());
		$this->assertContains($prefix, $this->query->getPrefixes());

		$prefixes = $this->query->getPrefixes();
		$this->assertTrue(count($prefixes) == 1);
		$this->assertEquals($prefix, $prefixes[0]);

		$this->query->removePrefixes();
		$prefixes = $this->query->getPrefixes();
		$this->assertTrue(empty($prefixes));

		$this->query->addPrefix($prefix);
		$this->query->removePrefix(0);
		$prefixes = $this->query->getPrefixes();
		$this->assertTrue(empty($prefixes));
	}

	public function testBase() {
		$base = new \Erfurt\Sparql\Query2\IriRef("http://example.com");
		$this->query->setBase($base);
		$this->assertEquals($base, $this->query->getBase());
		$this->assertTrue($this->query->hasBase());
		$this->query->removeBase();
		$this->assertFalse($this->query->hasBase());
	}

	public function testDistinctReduced() {
		$this->assertFalse($this->query->isReduced());
		$this->assertFalse($this->query->isDistinct());

		$this->query->setDistinct(false);
		$this->assertFalse($this->query->isDistinct());

		$this->query->setReduced(false);
		$this->assertFalse($this->query->isReduced());

		$this->query->setDistinct();
		$this->assertTrue($this->query->isDistinct());
		$this->assertFalse($this->query->isReduced());

		$this->query->setReduced();
		$this->assertTrue($this->query->isReduced());
		$this->assertFalse($this->query->isDistinct());
	}

}

?>