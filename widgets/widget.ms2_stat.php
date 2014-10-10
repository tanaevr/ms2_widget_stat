<?php

/*

Статистика заказов

*/

$modx->addPackage('minishop2', MODX_CORE_PATH.'components/minishop2/model/');

$modx->getService('lexicon','modLexicon');
$modx->lexicon->load($modx->config['manager_language'].':minishop2:widget');

$q_where = "`date` + INTERVAL ".date('j')." DAY > NOW()";

//Статистика за текущий месяц
$chunkArr = array(
    'lang' => $modx->config['manager_language'],
    'new_count' => $modx->getCount('msOrder',array('status' => 1),$q_where),
    'canceled_count' => $modx->getCount('msOrder',array('status' => 4),$q_where),
    'done_count' => $modx->getCount('msOrder',array('status' => 2),$q_where),
    'all_count' => $modx->getCount('msOrder')
);
$pages = $modx->getCollection('msOrder', array('status' => 1));

$current_month = date('n');

$months = array(
	'1' => 'Январь',
	'2' => 'Февраль',
	'3' => 'Март',
	'4' => 'Апрель',
	'5' => 'Май',
	'6' => 'Июнь',
	'7' => 'Июль',
	'8' => 'Август',
	'9' => 'Сентябрь',
	'10' => 'Октябрь',
	'11' => 'Ноябрь',
	'12' => 'Декабрь',
);

//Статистика по месяцам
$stat_month = array();
$sql = "
SELECT month(`createdon`) AS `order_month`, count(*) AS `order_count`
FROM ".$modx->getTableName('msOrder')."
WHERE year(`createdon`) = ".date('Y')." 
GROUP BY month(`createdon`)
ORDER BY month(`createdon`)
LIMIT 5
";
$stmt = $modx->prepare($sql);
if ($stmt && $stmt->execute()) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $stat_month[] = array("name"=>$months[$row['order_month']],"count"=>$row['order_count']);
    }
    $stmt->closeCursor();
}

//print_r($stat_month);

$chunkArr['stat_month'] = json_encode($stat_month);

$tpl = <<<EOT
<script type="text/javascript">
Ext.chart.Chart.CHART_URL = 'assets/ext3/resources/charts.swf';
Ext.onReady(function(){
	
    var store = new Ext.data.JsonStore({
        fields: ['categorytitle', 'total'],
        data: [{
            categorytitle: 'Новые ([[+new_count]])',
            total: [[+new_count]]
        },{
            categorytitle: 'Оплаченые ([[+done_count]])',
            total: [[+done_count]]
        },{
            categorytitle: 'Отмененны ([[+canceled_count]])',
            total: [[+canceled_count]]
        }]
    });
    
    new Ext.Panel({
        width: 300,
        height: 250,
        title: 'Статистика за этот месяц',
        renderTo: 'ms2_stat',
        border: false,
        items: {
            store: store,
            xtype: 'piechart',
            dataField: 'total',
            categoryField: 'categorytitle',
            series: [{
                style: {
                    colors: ["#99CCFF", "#CCFFCC", "#FF99CC"]
                }
            }],
            extraStyle:{
                legend:{
                    display: 'bottom',
                    padding: 5,
                    font:{
                        family: 'Tahoma',
                        size: 11
                    }
                }
            }
        }
    });
    
    var store2 = new Ext.data.JsonStore({
        fields:['name', 'count'],
        data: [[+stat_month]]
    });
    
    new Ext.Panel({
        title: 'Статистика по месяцам',
        renderTo: 'ms2_stat2',
        //width:500,
        height:250,
        layout:'fit',
        border: false,
        items: {
            xtype: 'linechart',
            store: store2,
            xField: 'name',
            yField: 'count',
            border: false,
			listeners: {
				itemclick: function(o){
                /*
					var rec = store2.getAt(o.index);
                    MODx.msg.status({
                        title: 'Item Selected',
                        message: 'You chose: '+rec.get('name'),
                        delay: 3
                    });
                */
				}
			},
            series: [{
                type: 'column',
                displayName: '',
                yField: 'count',
                style: {
                    image:'bar.gif',
                    mode: 'stretch',
                    color:0x99BBE8
                }
            },{
                type:'line',
                displayName: '',
                yField: 'count',
                style: {
                    color: 0x15428B
                }
            }]
        }
    });
    
});
</script>

<table width="100%">
    <col width="*">
    <col width="30">
    <col width="*">
    <col width="*">
    <tr>        
        <td>&nbsp;</td>
        <td>
            <div id="ms2_stat"></div>
        </td>
        <td>
            <div id="ms2_stat2"></div>
        </td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td>
            <p style="color:#000;">Всего заказов: <b>[[+all_count]]</b></p>
        </td>
        <td>&nbsp;</td>
    </tr>
</table>
EOT;

$chunk = $modx->newObject('modChunk');
$chunk->fromArray(array('name'=>"INLINE-".uniqid(),'snippet'=>$tpl));
$chunk->setCacheable(false);

$output = $chunk->process($chunkArr);

return $output;
