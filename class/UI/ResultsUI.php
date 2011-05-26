<?php

  /**
   * ResultsUI
   *
   * This is the second half to the search procedure. (Starts in SearchUI.php)
   * ResultsUI shows the pager with search fields taken into account.
   *
   * @author Robert Bost <bostrt at tux dot appstate dot edu>
   */

PHPWS_Core::initModClass('intern', 'UI/UI.php');
class ResultsUI implements UI
{
    public static function display()
    {
        PHPWS_Core::initCoreClass('DBPager.php');
        PHPWS_Core::initModClass('intern', 'Internship.php');

        $dept = null;
        $term = null;
        $name = null;

        /**
         * Check if any search fields are set.
         */
        if(isset($_REQUEST['dept'])){
            $dept = $_REQUEST['dept'];
        }
        if(isset($_REQUEST['term_select'])){
            $term = $_REQUEST['term_select'];
        }
        if(isset($_REQUEST['name'])){
            $name = $_REQUEST['name'];
        }

        /* Automatically open the row with the matching ID. */
        $o = -1;
        if(isset($_REQUEST['o'])){
            $o = $_REQUEST['o'];
        }

        /* Get Pager */
        $pager = self::getPager($name, $dept, $term);
        $result = $pager->get();

        /* Javascript */
        javascript('/jquery/');
        javascript('open_window');
        javascript('confirm');
        javascript('/modules/intern/hider', array('OPEN' => $o));

        if(!is_null($pager->display_rows)){
            /* Build up the link for exporting rows to CSV. */
            $ids = array();
            foreach($pager->display_rows as $i){
                $ids[] = $i->id;
            }
            /* Add link to page. */
            javascript('/modules/intern/csv', 
                       array('link' => PHPWS_Text::moduleLink('Download Spreadsheet', 'intern', array('action' => 'csv', 'ids' => $ids))));
        }

        return $result;
    }

    /**
     * Get the DBPager object. Search strings can be passed in too.
     */
    private static function getPager($name=null, $deptId=null, $term=null)
    {
        $pager = new DBPager('intern_internship', 'Internship');
        $pager->setModule('intern');
        
        $pager->db->addJoin('LEFT', 'intern_internship', 'intern_student', 'student_id', 'id');
        $pager->db->addJoin('LEFT', 'intern_internship', 'intern_admin', 'department_id', 'department_id');
        if(!Current_User::isDeity())
            $pager->addWhere('intern_admin.username', Current_User::getUsername());

        // Search by department, term, and name/banner.
        if(!is_null($deptId) && $deptId != -1)
            $pager->addWhere('department_id', $deptId);
        if(!is_null($term) && $term != -1){
            $pager->addWhere('term', $term);
        }
        if(!is_null($name) && $name != ''){
            $pager->addWhere('intern_student.first_name', "%$name%", 'ILIKE', 'OR', 'namez');
            $pager->addWhere('intern_student.middle_name', "%$name%", 'ILIKE', 'OR', 'namez');
            $pager->addWhere('intern_student.last_name', "%$name%", 'ILIKE', 'OR', 'namez');
            $pager->addWhere('intern_student.banner', "%$name%", 'ILIKE', 'OR', 'namez');
        }
            
        $pager->setTemplate('results.tpl');
        $pager->addRowTags('getRowTags');
        $pager->setEmptyMessage('No Results');

        return $pager;
    }
}
?>