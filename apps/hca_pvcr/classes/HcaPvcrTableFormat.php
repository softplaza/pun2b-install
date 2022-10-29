<?php

class HcaPvcrTableFormat
{
    function getHeader()
    {
?>
    <thead>
    <tr class="sticky-under-menu">
        <th class="th1">Property info</th>
        <th>Move-Out</th>
        <th>Pre Walk</th>
        <th>Maintenance</th>
        <th>Painter</th>
        <th>Urine Scan</th>
        <th>Cleaning Service</th>
        <th>Vinyl</th>
        <th>Carpet</th>
        <th>Carpet Cleaning</th>
        <th>Refinish</th>
        <th>Pest-Control</th>
        <th>Final Walk</th>
        <th>Move-In</th>
    </tr>
</thead>
<?php
    }

    function getTbodyRow($cur_info)
    {
        $row_id = isset($_GET['row']) ? intval($_GET['row']) : 0;
        $td = $p = [];
        
        $p[] = '<p>'.html_encode($cur_info['pro_name']).'</p>';
        if ($cur_info['unit_number'] != '')
            $p[] = '<p><span>Unit #</span> '.html_encode($cur_info['unit_number']).'</p>';
        if ($cur_info['unit_size'] != '')
            $p[] = '<p><span>Unit size</span> '.html_encode($cur_info['unit_size']).'</p>';
        $td[] = '<td class="td1">'.implode("\n", $p).'</td>';

        $p = [];
        if ($cur_info['move_out_date'] > 0)
            $p[] = '<p>'.format_time($cur_info['move_out_date'], 1).'</p>';
        if ($cur_info['move_out_comment'] != '')
            $p[] = '<p>'.html_encode($cur_info['move_out_comment']).'</p>';
        $td[] = '<td class="col2" onclick="editCell('.$cur_info['id'].', 2)">'.implode("\n", $p).'</td>';

        $p = [];
        $p[] = '<p><input type="date"/></p>';
        $p[] = '<p><input type="text"/></p>';
        $p[] = '<p><textarea></textarea></p>';
        $td[] = '<td class="col3">'.implode("\n", $p).'</td>';

        $p = [];
        $td[] = '<td class="col4" onclick="editCell('.$cur_info['id'].', 4)">'.implode("\n", $p).'</td>';

        $td[] = '<td class="col5" onclick="editCell('.$cur_info['id'].', 5)">'.implode("\n", $p).'</td>';

        $td[] = '<td class="col6" onclick="editCell('.$cur_info['id'].', 6)">'.implode("\n", $p).'</td>';

        $td[] = '<td class="col7" onclick="editCell('.$cur_info['id'].', 7)">'.implode("\n", $p).'</td>';

        $td[] = '<td class="col8" onclick="editCell('.$cur_info['id'].', 8)">'.implode("\n", $p).'</td>';

        $td[] = '<td class="col9" onclick="editCell('.$cur_info['id'].', 9)">'.implode("\n", $p).'</td>';

        $td[] = '<td class="col10" onclick="editCell('.$cur_info['id'].', 10)">'.implode("\n", $p).'</td>';

        $td[] = '<td class="col11" onclick="editCell('.$cur_info['id'].', 11)">'.implode("\n", $p).'</td>';

        $td[] = '<td class="col12" onclick="editCell('.$cur_info['id'].', 12)">'.implode("\n", $p).'</td>';

        $td[] = '<td class="col13" onclick="editCell('.$cur_info['id'].', 13)">'.implode("\n", $p).'</td>';

        $td[] = '<td class="col14" onclick="editCell('.$cur_info['id'].', 14)">'.implode("\n", $p).'</td>';

        return '<tr id="row'.$cur_info['id'].'" class="'.($cur_info['id'] == $row_id ? 'anchor' : '').'">'.implode("\n", $td).'</tr>';

    }

}