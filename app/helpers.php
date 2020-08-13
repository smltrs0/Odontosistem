<?php
use Carbon\Carbon;
use Jenssegers\Date\Date as Date;

function diff_date_for_humans(Carbon $date) : string
{
    return (new Date($date->timestamp))->ago();
}
function diff_string_for_humans($stringDate) : string
{
    $date = Date::createFromFormat('Y-m-d H:i:s', $stringDate);
    return (new Date($date))->ago();
}


function scannerTableLabel($stringDate) : string
{
    $now = Date::now();
    $date = Date::createFromFormat('Y-m-d H:i:s', $stringDate);
    $printDate = (new Date($date))->ago();
    $color = $now > $date ? 'info' : 'danger';

    $res = '<span class="badge badge-'.$color.'" style="color:white;">SCANNER: ';
    $res .= $printDate ;
    $res .= '</span>';

    return $res;
}
