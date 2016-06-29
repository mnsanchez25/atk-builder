<?php

  /**
   * Node for keeping track of moduleversions.
   *
   * @author Ivo Jansch <ivo@achievo.org>
   *
   * @version $Revision: 1.1 $
   *
   * $Id: class.versioninfo.inc,v 1.1 2003/01/30 22:49:27 ivo Exp $
   */
  class versioninfo extends atkNode
  {
    function versioninfo()
    {
      $this->atkNode("versioninfo", NF_READONLY);
      
      $this->add(new atkAttribute("module" , AF_PRIMARY, 50));
      $this->add(new atkAttribute("version", 0, 15));
      
      $this->setTable("versioninfo");
    }
  }

?>