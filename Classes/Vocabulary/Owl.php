<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Vocabulary;

/*                                                                        *
 * This script belongs to the Erfurt framework.                           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 2 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/copyleft/gpl.html.                      *
 *                                                                        */
interface Owl extends VocabularyInterface {

	const NS							=	'http://www.w3.org/2002/07/owl#';
	const IMPORTS						=	'http://www.w3.org/2002/07/owl#imports';
	const ONTOLOGY						=	'http://www.w3.org/2002/07/owl#Ontology';
	const SAMEAS						=	'http://www.w3.org/2002/07/owl#sameAs';
	const DIFFERENTFROM					=	'http://www.w3.org/2002/07/owl#differentFrom';
	const CARDINALITY					=	'http://www.w3.org/2002/07/owl#cardinality';
	const MINCARDINALITY				=	'http://www.w3.org/2002/07/owl#minCardinality';
	const MAXCARDINALITY				=	'http://www.w3.org/2002/07/owl#maxCardinality';
	const HASVALUE						=	'http://www.w3.org/2002/07/owl#hasValue';
	const SOMEVALUESFROM				=	'http://www.w3.org/2002/07/owl#someValuesFrom';
	const ALLVALUESFROM					=	'http://www.w3.org/2002/07/owl#allValuesFrom';
	const INTERSECTIONOF				=	'http://www.w3.org/2002/07/owl#intersectionOf';
	const EQUIVALENTCLASS				=	'http://www.w3.org/2002/07/owl#equivalentClass';
	const DISJOINTWITH					=	'http://www.w3.org/2002/07/owl#disjointWith';
	const OWL_CLASS						=	'http://www.w3.org/2002/07/owl#Class';
	const DEPRECATED_CLASS				=	'http://www.w3.org/2002/07/owl#DeprecatedClass';
	const ANNOTATION_PROPERTY			=	'http://www.w3.org/2002/07/owl#AnnotationProperty';
	const ONTOLOGY_PROPERTY				=	'http://www.w3.org/2002/07/owl#OntologyProperty';
	const DATATYPE_PROPERTY				=	'http://www.w3.org/2002/07/owl#DatatypeProperty';
	const OBJECT_PROPERTY				=	'http://www.w3.org/2002/07/owl#ObjectProperty';
	const FUNCTIONAL_PROPERTY			=	'http://www.w3.org/2002/07/owl#FunctionalProperty';
	const INVERSEFUNCTIONAL_PROPERTY	=	'http://www.w3.org/2002/07/owl#InverseFunctionalProperty';
	const SYMMETRIC_PROPERTY			=	'http://www.w3.org/2002/07/owl#SymmetricProperty';
	const TRANSITIVE_PROPERTY			=	'http://www.w3.org/2002/07/owl#TransitiveProperty';
	const DEPRECATED_PROPERTY			=	'http://www.w3.org/2002/07/owl#DeprecatedProperty';
	const RESTRICTION					=	'http://www.w3.org/2002/07/owl#Restriction';
	const ONEOF							=	'http://www.w3.org/2002/07/owl#oneOf';
	const THING							=	'http://www.w3.org/2002/07/owl#Thing';
	const ONPROPERTY					=	'http://www.w3.org/2002/07/owl#onProperty';
	const ALLDIFFERENT					=	'http://www.w3.org/2002/07/owl#AllDifferent';
	const UNIONOF						=	'http://www.w3.org/2002/07/owl#unionOf';
	const COMPLEMENTOF					=	'http://www.w3.org/2002/07/owl#complementOf';

}

?>