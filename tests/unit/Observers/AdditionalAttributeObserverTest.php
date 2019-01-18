<?php

/**
 * TechDivision\Import\Observers\AdditionalAttributeObserverTest
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
*
* PHP version 5
*
* @author    Tim Wagner <t.wagner@techdivision.com>
* @copyright 2016 TechDivision GmbH <info@techdivision.com>
* @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
* @link      https://github.com/techdivision/import
* @link      http://www.techdivision.com
*/

namespace TechDivision\Import\Observers;

use TechDivision\Import\Utils\ColumnKeys;

/**
 * Test class for the additional attribute observer implementation.
 *
 * @author    Tim Wagner <t.wagner@techdivision.com>
 * @copyright 2016 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/import
 * @link      http://www.techdivision.com
 */
class AdditionalAttributeObserverTest extends \PHPUnit_Framework_TestCase
{

    /**
     * The additional attribute observer we want to test.
     *
     * @var \TechDivision\Import\Observers\AdditionalAttributeObserver
     */
    protected $additionalAttributeObserver;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     * @see \PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->additionalAttributeObserver = new AdditionalAttributeObserver();
    }

    /**
     * Test the handle() method.
     *
     * @return void
     */
    public function testHandle()
    {

        // prepare the expected row structure after the observer
        $expectedRow = array(
            0 => $additionalAttributes = sprintf('%s=%s,%s=%s', $col1 = 'attr1', $val1 = 'val1', $col2 = 'attr2', $val2 = 'val2'),
            1 => $val1,
            2 => $val2
        );

        // create a dummy CSV file row
        $row = array(0 => $additionalAttributes);

        // create a mock configuration
        $mockConfiguration = $this->getMockBuilder('TechDivision\Import\ConfigurationInterface')
                                  ->setMethods(get_class_methods('TechDivision\Import\ConfigurationInterface'))
                                  ->getMock();
        $mockConfiguration->expects($this->once())
                          ->method('getDelimiter')
                          ->willReturn(',');
        $mockConfiguration->expects($this->once())
                          ->method('getEnclosure')
                          ->willReturn('"');
        $mockConfiguration->expects($this->once())
                          ->method('getEscape')
                          ->willReturn('\\');

        // create a mock subject configuruation
        $mockSubjectConfiguration = $this->getMockBuilder('TechDivision\Import\Configuration\SubjectConfigurationInterface')
                                         ->setMethods(get_class_methods('TechDivision\Import\Configuration\SubjectConfigurationInterface'))
                                         ->getMock();
        $mockSubjectConfiguration->expects($this->once())
                                 ->method('getConfiguration')
                                 ->willReturn($mockConfiguration);

        // mock a system logger
        $mockSystemLogger = $this->getMockBuilder('Psr\Log\LoggerInterface')
                                 ->setMethods(get_class_methods('Psr\Log\LoggerInterface'))
                                 ->getMock();
        $mockSystemLogger->expects($this->exactly(2))
                         ->method('debug')
                         ->withConsecutive(
                             array(
                                 sprintf(
                                     'Extract new column "%s" with value "%s" from column "%s" in file %s on line %d',
                                     $col1,
                                     $val1,
                                     ColumnKeys::ADDITIONAL_ATTRIBUTES,
                                     $filename = 'product-import_20170712-120012_01.csv',
                                     $lineNumber = 2
                                 )
                             ),
                             array(
                                 sprintf(
                                     'Extract new column "%s" with value "%s" from column "%s" in file %s on line %d',
                                     $col2,
                                     $val2,
                                     ColumnKeys::ADDITIONAL_ATTRIBUTES,
                                     $filename,
                                     $lineNumber
                                  )
                             )
                         )
                         ->willReturnOnConsecutiveCalls(null, null);

        // mock a subject
        $mockSubject = $this->getMockBuilder('TechDivision\Import\Subjects\SubjectInterface')
                            ->setMethods(get_class_methods('TechDivision\Import\Subjects\SubjectInterface'))
                            ->getMock();
        $mockSubject->expects($this->any())
                    ->method('getConfiguration')
                    ->willReturn($mockSubjectConfiguration);
        $mockSubject->expects($this->any())
                    ->method('isDebugMode')
                    ->willReturn(true);
        $mockSubject->expects($this->once())
                    ->method('getRow')
                    ->willReturn($row);
        $mockSubject->expects($this->exactly(2))
                    ->method('getSystemLogger')
                    ->willReturn($mockSystemLogger);
        $mockSubject->expects($this->exactly(2))
                    ->method('getFilename')
                    ->willReturn($filename);
        $mockSubject->expects($this->exactly(2))
                    ->method('getLineNumber')
                    ->willReturn($lineNumber);
        $mockSubject->expects($this->any())
                    ->method('hasHeader')
                    ->withConsecutive(
                        array(ColumnKeys::ADDITIONAL_ATTRIBUTES),
                        array($col1),
                        array($col2)
                    )
                    ->willReturnOnConsecutiveCalls(true, false, false);
        $mockSubject->expects($this->exactly(3))
                    ->method('getHeader')
                    ->withConsecutive(
                        array(ColumnKeys::ADDITIONAL_ATTRIBUTES),
                        array($col1),
                        array($col2)
                    )
                    ->willReturnOnConsecutiveCalls(0, 1, 2);
        $mockSubject->expects($this->exactly(2))
                    ->method('addHeader')
                    ->withConsecutive(
                        array($col1),
                        array($col2)
                     )
                    ->willReturn(0);
        $mockSubject->expects($this->exactly(2))
                    ->method('explode')
                    ->withConsecutive(
                        array(sprintf('%s=%s', $col1, $val1)),
                        array(sprintf('%s=%s', $col2, $val2))
                    )
                    ->willReturnOnConsecutiveCalls(
                        array($col1, $val1),
                        array($col2, $val2)
                    );

        // let the subject handle the additional attributes
        $this->assertSame($expectedRow, $this->additionalAttributeObserver->handle($mockSubject));
    }

    /**
     * Test the handle() method with more complex data.
     *
     * @return void
     */
    public function testHandleWithComplexData()
    {

        // initialize the column names
        $col1  = 'manufacturer_accessoires';
        $col2  = 'color_careproduct';
        $col3  = 'length';
        $col4  = 'width_accessoires';
        $col5  = 'height';
        $col6  = 'material_careproduct';
        $col7  = 'amount_careproduct';
        $col8  = 'usage';
        $col9  = 'valid_for';
        $col10 = 'size_careproduct';
        $col11 = 'weight_careproduct';

        // prepare the expected row structure after the observer
        $expectedRow = array(
            0  => $additionalAttributes = '"manufacturer_accessoires=Gabor Shoe Care","color_careproduct=weiss","length=38","width_accessoires=48","height=152","material_careproduct=","amount_careproduct=75 ml","usage=Groben Staub und Schmutz entfernen. Creme dünn und gleichmäßig auftragen. Trocknen lassen, anschließend mit einem weichen Tuch auspolieren.","valid_for=Für alle Glattleder sowie Soft- und Anilinleder.","size_careproduct=","weight_careproduct=101"',
            1  => $val1 = 'Gabor Shoe Care',
            2  => $val2 = 'weiss',
            3  => $val3 = '38',
            4  => $val4 = '48',
            5  => $val5 = '152',
            6  => $val6 = '',
            7  => $val7 = '75 ml',
            8  => $val8 = 'Groben Staub und Schmutz entfernen. Creme dünn und gleichmäßig auftragen. Trocknen lassen, anschließend mit einem weichen Tuch auspolieren.',
            9  => $val9 = 'Für alle Glattleder sowie Soft- und Anilinleder.',
            10 => $val10 = '',
            11 => $val11 = '101'
        );

        // create a dummy CSV file row
        $row = array(0 => $additionalAttributes);

        // create a mock configuration
        $mockConfiguration = $this->getMockBuilder('TechDivision\Import\ConfigurationInterface')
            ->setMethods(get_class_methods('TechDivision\Import\ConfigurationInterface'))
            ->getMock();
        $mockConfiguration->expects($this->once())
            ->method('getDelimiter')
            ->willReturn(',');
        $mockConfiguration->expects($this->once())
            ->method('getEnclosure')
            ->willReturn('"');
        $mockConfiguration->expects($this->once())
            ->method('getEscape')
            ->willReturn('\\');

        // create a mock subject configuruation
        $mockSubjectConfiguration = $this->getMockBuilder('TechDivision\Import\Configuration\SubjectConfigurationInterface')
            ->setMethods(get_class_methods('TechDivision\Import\Configuration\SubjectConfigurationInterface'))
            ->getMock();
        $mockSubjectConfiguration->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($mockConfiguration);

        // mock a system logger
        $mockSystemLogger = $this->getMockBuilder('Psr\Log\LoggerInterface')
            ->setMethods(get_class_methods('Psr\Log\LoggerInterface'))
            ->getMock();
        $mockSystemLogger->expects($this->exactly(11))
            ->method('debug')
            ->withConsecutive(
                array(
                    sprintf(
                        'Extract new column "%s" with value "%s" from column "%s" in file %s on line %d',
                        $col1,
                        $val1,
                        ColumnKeys::ADDITIONAL_ATTRIBUTES,
                        $filename = 'product-import_20170712-120012_01.csv',
                        $lineNumber = 2
                    )
                ),
                array(
                    sprintf(
                        'Extract new column "%s" with value "%s" from column "%s" in file %s on line %d',
                        $col2,
                        $val2,
                        ColumnKeys::ADDITIONAL_ATTRIBUTES,
                        $filename,
                        $lineNumber
                    )
                ),
                array(
                    sprintf(
                        'Extract new column "%s" with value "%s" from column "%s" in file %s on line %d',
                        $col3,
                        $val3,
                        ColumnKeys::ADDITIONAL_ATTRIBUTES,
                        $filename,
                        $lineNumber
                    )
                ),
                array(
                    sprintf(
                        'Extract new column "%s" with value "%s" from column "%s" in file %s on line %d',
                        $col4,
                        $val4,
                        ColumnKeys::ADDITIONAL_ATTRIBUTES,
                        $filename,
                        $lineNumber
                    )
                ),
                array(
                    sprintf(
                        'Extract new column "%s" with value "%s" from column "%s" in file %s on line %d',
                        $col5,
                        $val5,
                        ColumnKeys::ADDITIONAL_ATTRIBUTES,
                        $filename,
                        $lineNumber
                    )
                ),
                array(
                    sprintf(
                        'Extract new column "%s" with value "%s" from column "%s" in file %s on line %d',
                        $col6,
                        $val6,
                        ColumnKeys::ADDITIONAL_ATTRIBUTES,
                        $filename,
                        $lineNumber
                    )
                ),
                array(
                    sprintf(
                        'Extract new column "%s" with value "%s" from column "%s" in file %s on line %d',
                        $col7,
                        $val7,
                        ColumnKeys::ADDITIONAL_ATTRIBUTES,
                        $filename,
                        $lineNumber
                    )
                ),
                array(
                    sprintf(
                        'Extract new column "%s" with value "%s" from column "%s" in file %s on line %d',
                        $col8,
                        $val8,
                        ColumnKeys::ADDITIONAL_ATTRIBUTES,
                        $filename,
                        $lineNumber
                    )
                ),
                array(
                    sprintf(
                        'Extract new column "%s" with value "%s" from column "%s" in file %s on line %d',
                        $col9,
                        $val9,
                        ColumnKeys::ADDITIONAL_ATTRIBUTES,
                        $filename,
                        $lineNumber
                    )
                ),
                array(
                    sprintf(
                        'Extract new column "%s" with value "%s" from column "%s" in file %s on line %d',
                        $col10,
                        $val10,
                        ColumnKeys::ADDITIONAL_ATTRIBUTES,
                        $filename,
                        $lineNumber
                    )
                ),
                array(
                    sprintf(
                        'Extract new column "%s" with value "%s" from column "%s" in file %s on line %d',
                        $col11,
                        $val11,
                        ColumnKeys::ADDITIONAL_ATTRIBUTES,
                        $filename,
                        $lineNumber
                    )
                )
            );

        // mock a subject
        $mockSubject = $this->getMockBuilder('TechDivision\Import\Subjects\SubjectInterface')
            ->setMethods(get_class_methods('TechDivision\Import\Subjects\SubjectInterface'))
            ->getMock();
        $mockSubject->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($mockSubjectConfiguration);
        $mockSubject->expects($this->any())
            ->method('isDebugMode')
            ->willReturn(true);
        $mockSubject->expects($this->once())
            ->method('getRow')
            ->willReturn($row);
        $mockSubject->expects($this->exactly(11))
            ->method('getSystemLogger')
            ->willReturn($mockSystemLogger);
        $mockSubject->expects($this->exactly(11))
            ->method('getFilename')
            ->willReturn($filename);
        $mockSubject->expects($this->exactly(11))
            ->method('getLineNumber')
            ->willReturn($lineNumber);
        $mockSubject->expects($this->exactly(12))
            ->method('hasHeader')
            ->withConsecutive(
                array(ColumnKeys::ADDITIONAL_ATTRIBUTES),
                array($col1),
                array($col2),
                array($col3),
                array($col4),
                array($col5),
                array($col6),
                array($col7),
                array($col8),
                array($col9),
                array($col10),
                array($col11)
            )
            ->willReturnOnConsecutiveCalls(true, false, false, false, false, false, false, false, false, false, false, false);
        $mockSubject->expects($this->exactly(12))
            ->method('getHeader')
            ->withConsecutive(
                array(ColumnKeys::ADDITIONAL_ATTRIBUTES),
                array($col1),
                array($col2),
                array($col3),
                array($col4),
                array($col5),
                array($col6),
                array($col7),
                array($col8),
                array($col9),
                array($col10),
                array($col11)
            )
            ->willReturnOnConsecutiveCalls(0, 1, 2, 3, 4, 5, 6, 7, 8, 9 ,10, 11);
        $mockSubject->expects($this->exactly(11))
            ->method('addHeader')
            ->withConsecutive(
                array($col1),
                array($col2),
                array($col3),
                array($col4),
                array($col5),
                array($col6),
                array($col7),
                array($col8),
                array($col9),
                array($col10),
                array($col11)
            )
            ->willReturn(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11);
        $mockSubject->expects($this->exactly(11))
            ->method('explode')
            ->withConsecutive(
                array(sprintf('%s=%s', $col1, $val1)),
                array(sprintf('%s=%s', $col2, $val2)),
                array(sprintf('%s=%s', $col3, $val3)),
                array(sprintf('%s=%s', $col4, $val4)),
                array(sprintf('%s=%s', $col5, $val5)),
                array(sprintf('%s=%s', $col6, $val6)),
                array(sprintf('%s=%s', $col7, $val7)),
                array(sprintf('%s=%s', $col8, $val8)),
                array(sprintf('%s=%s', $col9, $val9)),
                array(sprintf('%s=%s', $col10, $val10)),
                array(sprintf('%s=%s', $col11, $val11))
            )
            ->willReturnOnConsecutiveCalls(
                array($col1, $val1),
                array($col2, $val2),
                array($col3, $val3),
                array($col4, $val4),
                array($col5, $val5),
                array($col6, $val6),
                array($col7, $val7),
                array($col8, $val8),
                array($col9, $val9),
                array($col10, $val10),
                array($col11, $val11)
            );

        // let the subject handle the additional attributes
        $this->assertSame($expectedRow, $this->additionalAttributeObserver->handle($mockSubject));
    }
}