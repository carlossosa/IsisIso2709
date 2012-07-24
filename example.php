#! /usr/bin/php
<?php
@ini_set('auto_detect_line_endings', true);
include 'IsisIso2709RecordExtract.php';
include 'IsisIso2709Records.php';

$SQL = "INSERT INTO bncjm ( `id#CAMPOS#` ) VALUES ( 'NULL#VALUES#' );";
$omitir = array ('v1','v5','v10a','v10b','v10d','v10z','v11a','v11b',
                 'v11d','v11y','v11z','v12a','v122','v125','v13a','v13b','v13d','v13z','v15a','v15b','v15d','v15z','v16a','v16b',
                 'v16d','v16z','v20a','v20b','v20z','v21a','v21b','v21z','v22a','v22b','v22z','v35a','v35z','v40a','v40z','v71a',
                'v71b','v100a','v100b','v100c','v100d','v100e','v100f','v100g','v100h','v100i','v100j','v100u','v101a','v101b','v101c','v101d',
                'v101e','v101f','v101g','v101h','v101j','v102a','v102b','v105a','v105b','v106a','v110a','v115a','v115b','v116a','v117a','v120a',
                'v121a','v121b','v123a','v123b','v123c','v123d','v123e','v123f','v123g','v123h','v123i','v123j','v123k','v123m','v123n','v123o',
                'v124a','v124b','v124c','v124d','v124e','v124f','v124g','v125a','v125b','v126a','v126b','v127a','v128a','v128b','v128c','v130a',
                'v131a','v131b','v131c','v131d','v131e','v131f','v131g','v131h','v131i','v131j','v131k','v131l','v135a','v140a','v141a','articulo',
                'v2005','v200a','v200b','v200c','v200d','v200e','v200f','v200g','v200h','v200i','v200v','v200z','v205a','v205b','v205d','v205f',
                'v205g','v206a','v207a','v207z','v208a','v208d','v210a','v210b','v210c','v210d','v210e','v210g','v210h','v211a','v215a','v215c',
                'v215d','v215e','v225a','v225d','v225e','v225f','v225h','v225i','v225v','v225x','v225z','v230a','v300a','v301a','v302a','v303a',
                'v304a','v305a','v306a','v307a','v308a','v310a','v311a','v312a','v313a','v314a','v315a','v316a','v3165','v317a','v3175','v318a',
                'v318b','v318c','v318d','v318e','v318f','v318h','v318i','v318j','v318k','v318l','v318n','v318o','v318p','v318r','v3185','v320a',
                'v321a','v321b','v321x','v322a','v323a','v324a','v325a','v326a','v326b','v327a','v328a','v330a','v332a','v333a','v336a','v337a',
                'v345a','v410','v411','v421','v422','v436','v441','v442','v443','v444','v445','v446','v447','v448','v451','v452','v453','v454',
                'v455','v456','v88','v470','v481','v400a','v423a','v430','v431','v432','v433','v434','v435','v437','v440','v46-','v461','v462',
                'v463','v464','v482','v488','v500a','v500b','v500h','v500i','v500k','v500l','v500m','v500n','v500q','v500r','v500s','v500u',
                'v500v','v500w','v500x','v500y','v500z','v5002','v5003','v501a','v501b','v501e','v501k','v501m','v501r','v501s',
                'v501u','v501w','v501x','v501y','v501z','v5012','v5013','v503a','v503b','v503d','v503e','v503f','v503h','v503i','v503j','v503k','v503l',
                'v503m','v503n','v510a','v510e','v510h','v510i','v510j','v510n','v510z','v512a',
                'v512e','v513a','v513e','v513h','v513i','v514a','v514e','v515a','v516a','v516e',
                'v517a','v517e','v518a','v520a','v520e','v520h','v520i','v520j','v520n','v520x',
                'v530a','v531a','v531b','v531v','v532a','v532z','v540a','v541a','v541e','v541h',
                'v541i','v541z','v545a','v600a','v600b','v600c','v600d','v600f','v600g','v600j',
                'v600p','v600t','v600x','v600y','v600z','v6002','v6003','v601a','v601b','v601c','v601d',
                'v601e','v601f','v601g','v601h','v601j','v601t','v601x','v601y',
                'v601z','v6012','v6013','v602a','v602f','v602j','v602t','v602x',
                'v602y','v602z','v6022','v6023','v6041','v605a','v605h','v605i',
                'v605j','v605k','v605l','v605m','v605n','v605q','v605r','v605s',
                'v605u','v605x','v605y','v605z','v6052','v6053','v606a','v606x',
                'v606y','v606z','v6062','v6063','v607a','v607x','v607y','v607z','v6072',
                'v6073','v608a','v608x','v608y','v608z','v6082','v6083','v6085',
                'v615a','v615x','v615n','v615m','v6152','v6153','v620a','v620b',
                'v620c','v620d','v6203','v626a','v626b','v626c','v660a','v661a',
                'v670b','v670c','v670e','v670z','v675a','v675v','v675z','v676a',
                'v676v','v676z','v680a','v680b','v686a','v686b','v686c','v686d',
                'v6862','v700a','v700b','v700c','v700d','v700f','v700g','v700p',
                'v7003','v7004','v7009','v701a','v701b','v701c','v701d','v701f',
                'v701g','v701p','v7013','v7014','v702a','v702b','v702c','v702d',
                'v702f','v702g','v702p','v7023','v7024','v710a','v710b','v710c',
                'v710d','v710e','v710f','v710g','v710h','v7103','v7104','v711a',
                'v711b','v711c','v711d','v711f','v711g','v711h','v7113','v7114',
                'v712a','v712b','v712c','v712d','v712e','v712f','v712g','v712h',
                'v7123','v7124','v720a','v720f','v7203','v7204','v721a','v721f',
                'v7213','v7214','v722a','v722f','v7223','v7224','v7225','v730a',
                'v7304','v790a','v790b','v801a','v801b','v801c','v801g','v802a',
                'v830a','v856a','v856b','v856c','v856d','v856e','v856f','v856g',
                'v856h','v856i','v856j','v856k','v856l','v856n','v856o','v856p',
                'v856q','v856r','v856s','v856t','v856u','v856v','v856w','v856x',
                'v856y','v856z','v886a','v886b','v8862','v920','v920a','v920b',
                'v921','v922','v923','v924','v925','v928a','v928b','v928c',
                'v928d','v928e','v928f','v928g','v928h','v929a','v929b','v929c',
                'v929d','v929e','v929f','v929g','v929h','v929i','v929j','v929k',
                'v929l','v929m','v929n','v929o','v930a','v930b','v930c','v930d',
                'v930e','v930f','v930g','v931','v932','v933','v934','v935',
                'v936','v937','v938','v939','v940a','v940b','v940c','v940d',
                'v940e','v940f','v940g','v940h','v940i','v940j','v940k','v940l',
                'v940m','v940n','v940o','v9409','v941a','v941b','v941c','v941d',
                'v941e','v941f','v941g','v941h','v941i','v941j','v941k','v941l','v941m',
                'v941n','v941o','v9419','v942a','v942b','v942c','v942d','v942e',
                'v942f','v942g','v942h','v942i','v942j','v942k','v942l','v942m',
                'v942n','v942o','v9429','v943a','v943b','v943c','v943d','v943e',
                'v943f','v943g','v943h','v943i','v943j','v943k','v943l','v943m',
                'v943n','v943o','v9439','v944a','v944b','v944c','v944d','v944e',
                'v944f','v944g','v944h','v944i','v944j','v944k','v944l','v944m',
                'v944n','v944o','v9449','v945a','v945b','v947a','v947b','v949',
                'v950','v951a','v951b','v951c','v951d','v951e','v951f','v951g',
                'v951h','v951i','v951j','v951k','v951l','v951m','v951n','v951o',
                'v9519','v952a','v952b','v952c','v952d','v952e','v952f','v952g',
                'v952h','v952i','v952j','v952k','v952l','v952m','v952n','v952o',
                'v9529','v953a','v953b','v953c','v953d','v953e','v953f','v953g',
                'v953h','v953i','v953j','v953k','v953l','v953m','v953n','v953o',
                'v9539','v966','v990a','v990b','v997','v998','v999a');

$files = array( '53258-56555.ISO', '56556-56607.ISO', '56608.ISO');
$count = 0;
foreach ( $files as $file)
{
$x = new IsisIso2709Records( $file);
$salida = "";
foreach (  $x as $idr => $j)//TODO LOS REGISTROS
{
$campos = "";
$values = "";
foreach (  $j as $k => $v) //TODOS LOS CAMPOS
{
    $arr = array();
    if (is_array($v)){
       
        foreach ( $v as $sC=>$cC) //TODOS LOS SUBCAMPOS
        {            
            if ( in_array( trim('v'.intval($k).strtolower($sC)), $omitir) &&
                    !in_array( trim('v'.intval($k).strtolower($sC)), array('v'.intval($k).'i1','v'.intval($k).'i2')) &&
                        !in_array( trim('v'.intval($k).strtolower($sC)), array_keys($arr))
                        )                
                    {
                        if (strlen(trim($cC)) > 0)
                            $arr['v'.intval($k).$sC] = mysql_escape_string($cC);
                    }
        }
        
    } else {
        if ( (strlen(trim($v)) > 0) && in_array( trim('v'.intval($k)), $omitir) && 
                !in_array( trim('v'.intval($k)), array_keys($arr)) )
            $arr['v'.intval($k)] = $v;
        if ( intval($k) == 1 )
                $arr['mfn'] = mysql_escape_string ($v);
    }
    if (count($arr) > 0)
    {
        $campos .= "` , `".implode("` , `", array_keys($arr));
        $values .= "' , '".implode("' , '", $arr);       
    }    
}
$temp = $salida = "";
$temp = preg_replace( '/#CAMPOS#/', $campos, $SQL);
$salida = preg_replace( '/#VALUES#/', $values, $temp)."\r\n";
file_put_contents('salida.sql', $salida, FILE_APPEND);
echo "[".$file."] => ".$idr." @ ".$count++."\r\n";
}
}

?>