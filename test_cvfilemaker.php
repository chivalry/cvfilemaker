<?php
require_once( 'CVFileMaker.php' );

abstract class CVFileMakerTest extends UnitTestCase {
  
  protected $standardDB = 'BYB_MSP';
  protected $standardHS = 'http://buildyourbdr.com/';
  protected $standardUN = 'Web User';
  protected $standardPW = 'g.i.joe';
  
  protected $standardProperties;
  
  protected $standardTableDef = array( 'Globals', 'Quotes', 'Servers', 'Users' );
  
  protected $customDB = 'ReachInsights';
  protected $customHS = 'http://64.71.231.45';
  protected $customUN = 'WebUser';
  protected $customPW = 'g.i.joe';
  
  protected $customProperties;
  
  protected $customTableDef = array(
    'Choices'       => array( 'layout' => 'Web>Choices',
                              'key'    => 'KP_Choices_ID' ),
    'Exams'         => array( 'layout' => 'Web>Exams',
                              'key'    => 'KP_Exam_ID' ),
    'People'        => array( 'layout' => 'Web>People',
                              'key'    => 'KP_Person_ID' ),
    'Questions'     => array( 'layout' => 'Web>Questions',
                              'key'    => 'KP_Question_ID' ),
    'RecordedExams' => array( 'layout' => 'Web>RecordedExam',
                              'key'    => 'KP_RecordedExam_ID' ),
    'Responses'     => array( 'layout' => 'Web>Responses',
                              'key'    => 'KP_Response_ID' ) );
}
?>