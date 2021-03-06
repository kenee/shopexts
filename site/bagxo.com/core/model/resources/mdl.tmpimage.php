<?php
include_once('shopObject.php');

class mdl_tmpimage extends shopObject{

    var $idColumn = 'id';
    var $textColumn = 'name';
    var $adminCtl = 'system/tmpimage';
    var $defaultCols = 'name,filetype,memo';


    function getColumns(){
        return array(
                    'id'=>array('label'=>'唯一标识','class'=>'span-4'),    /* 模板名-文件名 */
                    'name'=>array('label'=>'文件名','class'=>'span-4'),    /* 文件名 */
                    'filetype'=>array('label'=>'文件类型','class'=>'span-3'),    /* 文件类型 */
                    'memo'=>array('label'=>'文件说明','class'=>'span-4'),    /* 文件说明 */
                );
    }
    
    function getId($strId){
        $aTmp = explode('-', $strId);
        $aRet['tmpid'] = $aTmp[0];
        $aRet['name'] = substr($strId, strlen($aTmp[0])+1);
        return $aRet;
    }
    
    function getList($cols,$filter='',$start=0,$limit=20,&$count,$orderType=null){
        $data = $this->_fileList($filter);
        $count=count($data);
    
        if($orderType){
            foreach($data as $key => $rows){
                $order[$key]=strtolower($rows[$orderType[0]]);
            }

            if($orderType[1]=='asc'){
                array_multisort($order, SORT_ASC, $data);
            }else{
                array_multisort($order, SORT_DESC, $data);
            }
        }

        $data =array_slice($data,($start/20)*$limit,$limit);
        return $data;
    }
    
    function _fileList($filter){
        
        $dir = THEME_DIR.'/'.$filter['tmpid'].'/images/';
        $dirhandle=@opendir($dir);
        $ftype = array(
                'gif'=>'图片文件',
                'jpg'=>'图片文件',
                'jpeg'=>'图片文件',
                'png'=>'图片文件',
                'bmp'=>'图片文件',
                'css'=>'样式表文件',
                'js'=>'脚本文件',
            );
        while($file_name=@readdir($dirhandle)){
           
            if ($file_name!="." && $file_name!=".." && $file_name!="Thumbs.db" && !is_dir($dir.'/'.$file_name)){
                if(!$filter['show_bak'] && preg_match('/.*\\.bak_[0-9]+\\.[^\\.]+/',$file_name)){
                    continue;
                }
                $fext = strtolower(substr($file_name,strrpos($file_name,'.')+1));
                if(($filter['type']=='css')?($fext=='css'):($fext!='css') || $filter['type']=='all'){
                    $aRows[$file_name] = array('id'=> $filter['tmpid'].'-'.$file_name,
                            'name' => $file_name,
                            'filetype' => $fext,
                            'memo' => ($ftype[$fext]?$ftype[$fext]:'资源文件')
                        );
                }
            }
        }
        @closedir($dirhandle);
        ksort($aRows);
        return $aRows;
    }
    
    function _filter($filter){
        $where=array(1);
        $filter['to_type'] = 1;
        $where[] = 'for_id = 0';
        
        if($filter['msg_from']){
            $where[] = "msg_from ='".$filter['msg_from']."'";
        }

        return parent::_filter($filter).' AND '.implode($where,' AND ');
    }
    
    function getFile($sName, $tmpid){
        $aFile = $this->_fileList(array('tmpid'=>$tmpid,'show_bak'=>1,'type'=>'all'));

        $p = strrpos($sName,'.');
        $re = '/^'.preg_quote(substr($sName,0,$p)).'\.bak_([0-9]+)\.'.preg_quote(substr($sName,$p+1)).'$/';

        foreach($aFile as $key => $rows){
            if($rows['name'] == $sName){
                $file = $rows;
            }
            if(preg_match($re,$rows['name'])){
                $itms[] = $rows;
            }
        }
       
        $file['files'] = $itms;
        return $file;
    }
    
    function saveFile($aParams){
        $dir = THEME_DIR.'/'.$aParams['tmpid'].'/images/';
        if ($aParams['upfile']['size'] > 0){
            $image=$this->system->loadModel('system/storager');
            $limited=$image->get_pic_upload_max();
            if($aParams['upfile']['size']>$limited['size']){
                    return '上传图片不能大于'.$limited['desc'];
            }
            if ((substr($aParams['upfile']['type'],0,5)=="image") ){
                
                $file = $this->getFile($aParams['name'], $aParams['tmpid']);
                $aTmp = explode('.', $aParams['name']);
                $arrNum = count($aTmp) - 1;
                $lastStr = $aTmp[$arrNum];
                if(substr($aTmp[$arrNum-1], 0, 4) == 'bak_') $arrNum -= 1;
                for($i=0; $i<$arrNum; $i++){
                    $preStr .= $aTmp[$i].'.';
                }
                $iLoop = 1;
                foreach($file['files'] as $item){
                    if($item['name'] !== $preStr.'bak_'.$iLoop.'.'.$lastStr){
                        break;
                    }
                    $iLoop++;
                }
                $saveFile = $preStr.'bak_'.$iLoop.'.'.$lastStr;
                
                move_uploaded_file($aParams['upfile']['tmp_name'], $dir.$saveFile);
                chmod($dir.$saveFile,0666);
                $aParams['imgdef'] = $saveFile;
            }
        }
        
        $aParams['imgdef'] = basename($aParams['imgdef']);
        if($aParams['name'] != $aParams['imgdef']){
            copy($dir.$aParams['name'], $dir.'tmp_image');
            copy($dir.$aParams['imgdef'], $dir.$aParams['name']);
            copy($dir.'tmp_image', $dir.$aParams['imgdef']);
            unlink($dir.'tmp_image');
        }
        return true;
    }
    
    function saveSource($aParams){
        $dir = THEME_DIR.'/'.$aParams['tmpid'].'/images/';

        $aFile = $this->getFile($aParams['name'],$aParams['tmpid']);
        if(count($aFile['files']) == 0 || $aParams['isbak']){
            $aTmp = explode('.', $aFile['name']);
            $arrNum = count($aTmp) - 1;
            $lastStr = $aTmp[$arrNum];
            if(substr($aTmp[$arrNum-1], 0, 4) == 'bak_') $arrNum -= 1;
            for($i=0; $i<$arrNum; $i++){
                $preStr .= $aTmp[$i].'.';
            }
            $iLoop = 1;
            foreach($aFile['files'] as $item){
                if($item['name'] !== $preStr.'bak_'.$iLoop.'.'.$lastStr){
                    break;
                }
                $iLoop++;
            }
            $saveFile = $preStr.'bak_'.$iLoop.'.'.$lastStr;
            if($aParams['isbak']){
                file_rename($dir.$aParams['name'], $dir.$saveFile);
            }
        }

        $fp = fopen($dir.$aParams['name'],'wb');
        fwrite($fp,$aParams['file_source']);
        fclose($fp);
        return true;
    }
    
    function recoverSource($file, $dest, $tmpid){
        $dir = THEME_DIR.'/'.$tmpid.'/images/';
        return copy($dir.$file, $dir.$dest);
    }
    
    function toRemove($file, $tmpid){
        $dir = THEME_DIR.'/'.$tmpid.'/images/';
        return unlink($dir.$file);
    }
}
?>
