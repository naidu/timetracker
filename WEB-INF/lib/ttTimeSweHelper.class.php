<?php


class ttTimeSweHelper
{
  static function getAllDateRecords($from_date,$to_date) 
  {
    $result = array();

    $mdb2 = getConnection();
    global $user;
    $user_id = $user->getUser();
    $sql = "select l.id, l.user_id, l.date, TIME_FORMAT(l.start, '%k:%i') as start,
      TIME_FORMAT(sec_to_time(time_to_sec(l.start) + time_to_sec(l.duration)), '%k:%i') as finish,
      TIME_FORMAT(l.duration, '%k:%i') as duration,
      l.client_id, l.project_id, l.task_id, l.invoice_id, l.comment, l.billable, l.paid, l.status
      from tt_log l where l.status is not NULL and l.user_id = $user_id and l.date between $from_date and $to_date order by l.id";
    $res = $mdb2->query($sql);
    if (!is_a($res, 'PEAR_Error')) 
    {
      while ($val = $res->fetchRow()) 
      {
        $result[] = $val;
      }
    } 
    else 
    {
      return false;
    }
    return $result;
  }

  static function deleteEntry($id) 
  {
    global $user;
    $mdb2 = getConnection();

    // Delete associated files.
    if ($user->isPluginEnabled('at')) 
    {
      import('ttFileHelper');
      global $err;
      $fileHelper = new ttFileHelper($err);
      if (!$fileHelper->deleteEntityFiles($id, 'time'))
        return false;
    }

    $user_id = $user->getUser();
    $group_id = $user->getGroup();
    $org_id = $user->org_id;

    $modified_part = ', modified = now(), modified_ip = '.$mdb2->quote($_SERVER['REMOTE_ADDR']).', modified_by = '.$user->id;

    $sql = "update tt_log set status = null".$modified_part.
      " where id = $id and user_id = $user_id and group_id = $group_id and org_id = $org_id";
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

  static function getprojects() 
  {
    $result = array();

    $mdb2 = getConnection();
    global $user;
    $group_id = $user->group_id;
    $org_id = $user->org_id;
    $sql = "select id,name,projects as project_id,NULL as project from tt_clients as c where status is not NULL and group_id=$group_id and org_id=$org_id";
    $res = $mdb2->query($sql);
    if (!is_a($res, 'PEAR_Error')) 
    {
      while ($val = $res->fetchRow()) 
      {
        $result[] = $val;
      }
    } 
    else 
    {
      return false;
    }

    return $result;
  }

  static function getprojectsname($id) 
  {  
    $mdb2 = getConnection();
    global $user;
    $group_id = $user->group_id;
    $org_id = $user->org_id;
    $projects= explode(',',$id);
    
    foreach($projects as $pro)
    {  
      $sql = "select id,name from tt_projects where status is not NULL and group_id=$group_id and org_id=$org_id and id=$pro group by id";
      $res = $mdb2->query($sql);
      if (!is_a($res, 'PEAR_Error')) 
      {
        while ($val = $res->fetchRow()) 
        {
          $result[] = $val;
        }
      } 
      else 
      {
        return false;
      }
    }
    return $result;
  }

  static function getnullClientProjects() 
  {

    $mdb2 = getConnection();
    global $user;
    $group_id = $user->group_id;
    $org_id = $user->org_id;
  
    $sql = "select projects from tt_clients where group_id=$group_id and org_id=$org_id";
    $res = $mdb2->query($sql);
    if (!is_a($res, 'PEAR_Error')) 
    {
      while ($val = $res->fetchRow()) 
      {
        $get_projects[] = $val;
      }
    } 
    else
    {
      return false;
    } 
    
    $var=array();
    foreach ($get_projects as $pro)
    {
      $projects= explode(',',$pro[projects]);
      $var=array_merge($var,$projects);
    }
    $var1=implode(',',$var);
    $sql = 'select id,name from tt_projects where group_id=1 and org_id=1 and id not in'.'('. $var1.')';
    $res = $mdb2->query($sql);
      
    if (!is_a($res, 'PEAR_Error')) 
    {
      while ($val = $res->fetchRow()) 
      {
        $get_projects_for_null_clients[] = $val;
      }
    } 
    else
    {
      return false;
    } 
    
    return $get_projects_for_null_clients;
  }
}