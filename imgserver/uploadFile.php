<?php
/**
 * 接收另一个域名传递过来的文件和图片
 */
define('ROOT_PATH', str_replace('uploadFile.php', '', str_replace('\\', '/', __FILE__)));

$access_key = "";

$cus_remote_file_key = isset($_POST['access_key']) ? $_POST['access_key'] : "";

if($access_key != $cus_remote_file_key){
	exit("FAILED");
}

//允许的文件类型
$allow_file_types = '|GIF|JPG|JPEG|PNG|BMP|DOC|DOCX|XLS|XLSX|PPT|PPTX|MID|WAV|ZIP|RAR|PDF|TXT|';

//允许的操作来源
$promote_type_list = array('ueditor', 'gallery');

$promote_type = isset($_POST['promote_type']) ? trim($_POST['promote_type']) : '';

if(!in_array($promote_type, $promote_type_list)){
	exit("FAILED");
}

$file_dir = isset($_POST['dir']) ? trim($_POST['dir']) : '';

if(!$file_dir){
	exit("FAILED");
}else{
	if($promote_type == 'ueditor'){
		$file_dir = str_replace('../../../', '/', $file_dir);
	}elseif ($promote_type == 'gallery') {
		$file_dir = str_replace('../', '/', $file_dir);
	}
}

$file = isset($_FILES['file']) ? $_FILES['file'] : '';

if(!$file){
	exit("FAILED");
}

logResult(var_export($_POST, true));

if ( (isset($file['error']) && $file['error'] == 0) || (!isset($file['error']) && isset($file['tmp_name']) && $file['tmp_name'] != 'none') ){
	$tmpname = $file['tmp_name'];
	if(is_uploaded_file($tmpname)) {
		// 检查文件格式
		if (!check_file_type($tmpname, $file['name'], $allow_file_types)){
			exit("FAILED");
		}
		if($file['size'] > 4096000){
			exit("FAILED"); #文件过大
		}
		//检查目录是否存在，不存在则创建
		if(!file_exists('.'.$file_dir)){
			mkdir('.'.$file_dir, 0777, true); //允许创建多级目录
		}
		//移动文件
		$target_file = '.'.$file_dir.'/'.$file['name'];
		if(!move_uploaded_file($file['tmp_name'], $target_file)){
			exit("FAILED");
		}else{
			exit("SUCCESS");
		}
	}else{
		exit("FAILED");
	}
}

//未知错误
exit("FAILED");

/**
 * 检查文件类型
 *
 * @access      public
 * @param       string      filename            文件名
 * @param       string      realname            真实文件名
 * @param       string      limit_ext_types     允许的文件类型
 * @return      string
 */
function check_file_type($filename, $realname = '', $limit_ext_types = '')
{
    if ($realname)
    {
        $extname = strtolower(substr($realname, strrpos($realname, '.') + 1));
    }
    else
    {
        $extname = strtolower(substr($filename, strrpos($filename, '.') + 1));
    }

    if ($limit_ext_types && stristr($limit_ext_types, '|' . $extname . '|') === false)
    {
        return '';
    }

    $str = $format = '';

    $file = @fopen($filename, 'rb');
    if ($file)
    {
        $str = @fread($file, 0x400); // 读取前 1024 个字节
        @fclose($file);
    }
    else
    {
        if (stristr($filename, ROOT_PATH) === false)
        {
            if ($extname == 'jpg' || $extname == 'jpeg' || $extname == 'gif' || $extname == 'png' || $extname == 'doc' ||
                $extname == 'xls' || $extname == 'txt'  || $extname == 'zip' || $extname == 'rar' || $extname == 'ppt' ||
                $extname == 'pdf' || $extname == 'rm'   || $extname == 'mid' || $extname == 'wav' || $extname == 'bmp' ||
                $extname == 'swf' || $extname == 'chm'  || $extname == 'sql' || $extname == 'cert'|| $extname == 'pptx' || 
                $extname == 'xlsx' || $extname == 'docx')
            {
                $format = $extname;
            }
        }
        else
        {
            return '';
        }
    }

    if ($format == '' && strlen($str) >= 2 )
    {
        if (substr($str, 0, 4) == 'MThd' && $extname != 'txt')
        {
            $format = 'mid';
        }
        elseif (substr($str, 0, 4) == 'RIFF' && $extname == 'wav')
        {
            $format = 'wav';
        }
        elseif (substr($str ,0, 3) == "\xFF\xD8\xFF")
        {
            $format = 'jpg';
        }
        elseif (substr($str ,0, 4) == 'GIF8' && $extname != 'txt')
        {
            $format = 'gif';
        }
        elseif (substr($str ,0, 8) == "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A")
        {
            $format = 'png';
        }
        elseif (substr($str ,0, 2) == 'BM' && $extname != 'txt')
        {
            $format = 'bmp';
        }
        elseif ((substr($str ,0, 3) == 'CWS' || substr($str ,0, 3) == 'FWS') && $extname != 'txt')
        {
            $format = 'swf';
        }
        elseif (substr($str ,0, 4) == "\xD0\xCF\x11\xE0")
        {   // D0CF11E == DOCFILE == Microsoft Office Document
            if (substr($str,0x200,4) == "\xEC\xA5\xC1\x00" || $extname == 'doc')
            {
                $format = 'doc';
            }
            elseif (substr($str,0x200,2) == "\x09\x08" || $extname == 'xls')
            {
                $format = 'xls';
            } elseif (substr($str,0x200,4) == "\xFD\xFF\xFF\xFF" || $extname == 'ppt')
            {
                $format = 'ppt';
            }
        } elseif (substr($str ,0, 4) == "PK\x03\x04")
        {
            if (substr($str,0x200,4) == "\xEC\xA5\xC1\x00" || $extname == 'docx')
            {
                $format = 'docx';
            }
            elseif (substr($str,0x200,2) == "\x09\x08" || $extname == 'xlsx')
            {
                $format = 'xlsx';
            } elseif (substr($str,0x200,4) == "\xFD\xFF\xFF\xFF" || $extname == 'pptx')
            {
                $format = 'pptx';
            }else
            {
                $format = 'zip';
            }
        } elseif (substr($str ,0, 4) == 'Rar!' && $extname != 'txt')
        {
            $format = 'rar';
        } elseif (substr($str ,0, 4) == "\x25PDF")
        {
            $format = 'pdf';
        } elseif (substr($str ,0, 3) == "\x30\x82\x0A")
        {
            $format = 'cert';
        } elseif (substr($str ,0, 4) == 'ITSF' && $extname != 'txt')
        {
            $format = 'chm';
        } elseif (substr($str ,0, 4) == "\x2ERMF")
        {
            $format = 'rm';
        } elseif ($extname == 'sql')
        {
            $format = 'sql';
        } elseif ($extname == 'txt')
        {
            $format = 'txt';
        }
    }

    if ($limit_ext_types && stristr($limit_ext_types, '|' . $format . '|') === false)
    {
        $format = '';
    }

    return $format;
}

function logResult($word='') 
{
	$fp = fopen('./logs/'.date("Ymd").".txt","a");
	flock($fp, LOCK_EX) ;
	fwrite($fp,"执行日期：".strftime("%Y-%m-%d %H:%M:%S",time())."\n".$word."\n");
	flock($fp, LOCK_UN);
	fclose($fp);
}
?>
