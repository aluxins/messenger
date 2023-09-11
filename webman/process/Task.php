<?php
namespace process;

use Workerman\Crontab\Crontab;

class Task
{
    public $time_process = 0;
    
    public function onWorkerStart()
    {

        new Crontab('0 * * * * *', function(){
            //echo date('Y-m-d H:i:s')."\n";
            $this->time_process++;
        });
        
        new Crontab('0 0 */1 * * *', function(){
            echo date('Y-m-d H:i:s').": ";
            echo $this->time_process." min \n";
        });


    }
}