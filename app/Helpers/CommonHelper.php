<?php

function test()
{
    return "test helper";
}

function getProjectStatus($project)
{
    if (isset($project->status)) {
        return $project->status->name;
    }
    return '';
}

function getArraySelectOption()
{
    $array = ['20' => '20', '50' => '50', '100' => '100'];

    return $array;
}

function array_has_dupes($array)
{
    return count($array) !== count(array_flip($array));
}

function getRoleofVendor($vendor)
{
    $text = "";
    $arr_roles = array_unique($vendor->roles()->pluck('name')->toArray());
    foreach ($arr_roles as $key => $value) {
        if ($key === count($arr_roles) - 1) {
            $text = $text . $value;
        } else {
            $text = $text . $value. ",";
        }
    }
    return $text;
}

function getTotalManPowerofProcesses($processes)
{
    $total = 0;
    foreach ($processes as $item){
        $total = $total+$item->man_power;
    }
    return $total;
}

function getArrayManPower(){
    return [0.125,0.25,0.5,0.75,1];
}
