<?php
namespace Model\System;
use Spartan\Lib\Model;

defined('APP_NAME') or die('404 Not Found');

class Task extends Model{




    /**
     * 取得一个完整的下次任务时间
     * @return false|int
     */
    public function getNextRunTime(){
        $arrTime = Array(
            'week_day'=>$this->getData('week_day','-1'),
            'day'=>$this->getData('day','-1'),
            'hour'=>$this->getData('hour','-1'),
            'minute'=>$this->getData('minute','-1'),
        );
        return $this->taskNextRunTime($arrTime);
    }

    /**
     * 下一个运行时间
     * eg:$arrTime = $week_day=-1,$day=-1,$hour=-1,$minute=-1
     * @param array $arrTime
     * @return false|int
     */
    function taskNextRunTime($arrTime = []){
        if (isset($arrTime['minute']) && $arrTime['minute']){
            $arrTime['minute'] = explode(',',$arrTime['minute']);
        }
        list($n_year,$n_month,$n_day,$n_weekday) = explode('-', date('Y-m-d-w-H-i', time()));
        if($arrTime['week_day'] == -1){//不限制周
            if($arrTime['day'] == -1){//不限制日
                $firstDay = $n_day;
                $secondDay = $n_day + 1;
            }else{
                $firstDay = $arrTime['day'];
                $secondDay = $arrTime['day'] + date('t', time());
            }
        }else{
            $firstDay = $n_day + ($arrTime['week_day'] - $n_weekday);
            $secondDay = $firstDay + 7;
        }
        $firstDay < $n_day && $firstDay = $secondDay;
        if($firstDay == $n_day) {
            $todayTime = $this->todayNextRun($arrTime);
            if($todayTime['hour'] == -1 && $todayTime['minute'] == -1) {
                $arrTime['day'] = $secondDay;
                $nextTime = $this->todayNextRun($arrTime, 0, -1);
                $arrTime['hour'] = $nextTime['hour'];
                $arrTime['minute'] = $nextTime['minute'];
            } else {
                $arrTime['day'] = $firstDay;
                $arrTime['hour'] = $todayTime['hour'];
                $arrTime['minute'] = $todayTime['minute'];
            }
        } else {
            $arrTime['day'] = $firstDay;
            $nextTime = $this->todayNextRun($arrTime, 0, -1);
            $arrTime['hour'] = $nextTime['hour'];
            $arrTime['minute'] = $nextTime['minute'];
        }
        $arrTime['minute'] = max(0,$arrTime['minute']);
        $nextTime = @mktime($arrTime['hour'], $arrTime['minute'], 0, $n_month, $arrTime['day'], $n_year);
        return $nextTime <=time()?'':date('Y-m-d H:i:s',$nextTime);
    }

    /**
     * 下一天
     * @param $arrTime
     * @param int $hour
     * @param int $minute
     * @return array
     */
    function todayNextRun($arrTime, $hour = -2, $minute = -2) {
        $hour = $hour == -2 ? date('H', time()) : $hour;
        $minute = $minute == -2 ? date('i', time()) : $minute;
        $nextTime = array();
        if($arrTime['hour'] == -1 && !$arrTime['minute']) {
            $nextTime['hour'] = $hour;
            $nextTime['minute'] = $minute + 1;
        } elseif($arrTime['hour'] == -1 && $arrTime['minute'] != '') {
            $nextTime['hour'] = $hour;
            if(($nextMinute = $this->nextMinute($arrTime['minute'], $minute)) === false) {
                ++$nextTime['hour'];
                $nextMinute = $arrTime['minute'][0];
            }
            $nextTime['minute'] = $nextMinute;
        } elseif($arrTime['hour'] != -1 && $arrTime['minute'] == '') {
            if($arrTime['hour'] < $hour) {
                $nextTime['hour'] = $nextTime['minute'] = -1;
            } elseif($arrTime['hour'] == $hour) {
                $nextTime['hour'] = $arrTime['hour'];
                $nextTime['minute'] = $minute + 1;
            } else {
                $nextTime['hour'] = $arrTime['hour'];
                $nextTime['minute'] = 0;
            }
        } elseif($arrTime['hour'] != -1 && $arrTime['minute'] != '') {
            $nextMinute = $this->nextMinute($arrTime['minute'], $minute);
            if($arrTime['hour'] < $hour || ($arrTime['hour'] == $hour && $nextMinute === false)) {
                $nextTime['hour'] = -1;
                $nextTime['minute'] = -1;
            } else {
                $nextTime['hour'] = $arrTime['hour'];
                $nextTime['minute'] = $nextMinute;
            }
        }
        return $nextTime;
    }

    /**
     * 下一个分钟
     * @param $nextMinutes
     * @param $minuteNow
     * @return bool
     */
    function nextMinute($nextMinutes, $minuteNow) {
        foreach($nextMinutes as $nextMinute) {
            if($nextMinute > $minuteNow) {
                return $nextMinute;
            }
        }
        return false;
    }

} 