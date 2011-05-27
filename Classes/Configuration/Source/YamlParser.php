<?php
declare(ENCODING = 'utf-8');
namespace Erfurt\Configuration\Source;

/*                                                                        *
 * This script belongs to the FLOW3 package "YAML".                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require(EF_PATH_FRAMEWORK . 'Resources/PHP/HordeYaml/Yaml.php');
require(EF_PATH_FRAMEWORK . 'Resources/PHP/HordeYaml/Loader.php');
require(EF_PATH_FRAMEWORK . 'Resources/PHP/HordeYaml/Exception.php');
require(EF_PATH_FRAMEWORK . 'Resources/PHP/HordeYaml/Node.php');
require(EF_PATH_FRAMEWORK . 'Resources/PHP/HordeYaml/Dumper.php');

/**
 * Façade for a Yaml Parser and Dumper
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class YamlParser extends \Horde_Yaml {

}

?>