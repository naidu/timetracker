<?php


class ttTimeClassHelper
{
  static function getAllDateRecords($from_date,$to_date) {
    $result = array();

    $mdb2 = getConnection();
    global $user;
    $user_id = $user->getUser();
    $sql = "select l.id, l.user_id, l.date, TIME_FORMAT(l.start, '%k:%i') as start,
      TIME_FORMAT(sec_to_time(time_to_sec(l.start) + time_to_sec(l.duration)), '%k:%i') as finish,
      TIME_FORMAT(l.duration, '%k:%i') as duration,
      l.client_id, l.project_id, l.task_id, l.invoice_id, l.comment, l.billable, l.paid, l.status
      from tt_log l where l.user_id = $user_id and l.date between $from_date and $to_date order by l.id";
    $res = $mdb2->query($sql);
    if (!is_a($res, 'PEAR_Error')) {
      while ($val = $res->fetchRow()) {
        $result[] = $val;
      }
    } else return false;

    return $result;
  }
  static function deleteEntry($id) {
    global $user;
    $mdb2 = getConnection();

    // Delete associated files.
    if ($user->isPluginEnabled('at')) {
      import('ttFileHelper');
      global $err;
      $fileHelper = new ttFileHelper($err);
      if (!$fileHelper->deleteEntityFiles($id, 'time'))
        return false;
    }

    $user_id = $user->getUser();
    $group_id = $user->getGroup();
    $org_id = $user->org_id;



    $sql = "delete from tt_log  where id = $id";
    $affected = $mdb2->exec($sql);
    if ($affected==0)
      return "entry with this id doesn't exist";

    $sql = "update tt_custom_field_log set status = null".
      " where log_id = $id and group_id = $group_id and org_id = $org_id";
    $affected = $mdb2->exec($sql);
    if (is_a($affected, 'PEAR_Error'))
      return false;

    return true;
  }
  static function getprojects() {
    $result = array();

    $mdb2 = getConnection();
    global $user;
    $group_id = $user->group_id;
    $org_id = $user->org_id;
    $sql = "select p.id,p.name as project_name,p.description,p.tasks,p.group_id,p.org_id,NULL as client_name,c.address,c.tax 
                from tt_projects p,tt_clients c where p.id not in (select p.id from tt_projects p,tt_clients c where p.id=c.projects)  
                union  
                select p.id,p.name,p.description,p.tasks,p.group_id,p.org_id,c.name,c.address,c.tax 
                from tt_projects p,tt_clients c 
                where p.group_id=$group_id and p.org_id=$org_id and p.id=c.projects";
    $res = $mdb2->query($sql);
    if (!is_a($res, 'PEAR_Error')) {
      while ($val = $res->fetchRow()) {
        $result[] = $val;
      }
    } else return false;

    return $result;
  }
}