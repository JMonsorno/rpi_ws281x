<?php

class ApiRequest {

  private $parts = [];
  private $method = '';
  private $body = NULL;

  private $response = [];

  public function __construct(string $requestUri) {
    $this->parts = array_values(array_filter(explode('/', $requestUri), function($part) { return trim($part) != ''; }));
    $this->method = $_SERVER['REQUEST_METHOD'];
    $this->body = json_decode(file_get_contents('php://input'));
  }

  public function Process() {
    $apiKey = $this->parts[1];
    $mimic = new HueMimic();
    if (!$mimic->CheckApi($apiKey)) {
      $this->ErrorOut(1);
    }
    if (count($this->parts) > 2) {
      $object = $this->parts[2];
    } else {
      $this->ProcessAll();
    }
    switch(strtolower($object)) {
      case 'lights':
        $this->ProcessLights();
        break;
    }
  }

  private function ProcessAll() {
    //lights, groups, config, scenes, rules, sensors, resourcelinks
    if ($this->method != 'GET') {
      $this->ErrorOut(4);
    }
    $response = (object)['lights'=>(object)$this->GetLights()];
    echo json_encode($response);   
  }

  private function ProcessLights() {
    $range = $this->GetRange();
    if (count($range) == 0 && $this->method != 'GET') {
      $this->ErrorOut(4);
    }
    $lights = $this->GetLights();
    if (count($range) > 0) {
      $lights = array_filter($lights, function($lightNumber) use($range) {return in_array($lightNumber + 0, $range); }, ARRAY_FILTER_USE_KEY);
    }
    if (count($lights) == 0) {
      $this->ErrorOut(3);
    }
    $singleLight = array_values($lights)[0];
    $baseProps = $singleLight->GetWritable();
    if (count($this->parts) > 4) {
      $action = strtolower($this->parts[4]);
    } elseif (count($range) == 1 && $this->method == 'PUT') {
      $strand = DI::GetStrand();
      $writableBaseProps = array_filter($baseProps, function($propName) use($singleLight) { return !is_object($singleLight->$propName); });
      foreach(array_keys(get_object_vars($this->body)) as $propertyName) {
        if (in_array($propertyName, $writableBaseProps)) {
          if ($this->Castable($singleLight->$propertyName, $this->body->$propertyName)) {
            $singleLight->$propertyName = $this->Cast($singleLight->$propertyName, $this->body->$propertyName);
            $this->AddSuccessfulResponse($propertyName, $singleLight->$propertyName);
          } else {
            $this->AddErrorResponse(7, $propertyName);
          }
        } else {
          $this->AddErrorResponse(6, $propertyName);
        }
      }
      $strand->Save();
      $this->WriteResponseOut();
    } elseif($this->method == 'GET') {
      echo json_encode((object)$lights);
      exit(0);
    } else {
      $this->ErrorOut(4);
    }

    if ($this->method == 'GET') {
      $this->ErrorOut(3);
    } elseif ($this->method != 'PUT') {
      $this->ErrorOut(4);
    }

    $basePropObjects = array_filter($baseProps, function($propName) use($singleLight) { return is_object($singleLight->$propName); });
    if($action == "animate") {
      $strand = DI::GetStrand();
      $type = $this->body->type;
      $colors = NULL;
      @$colorsFromInput = $this->body->colors;
      if(isset($colorsFromInput) && is_array($colorsFromInput)) {
        $colors = array_map(function($c) { list($r, $g, $b) = $c; return Color::FromRGB($r, $g, $b); }, $colorsFromInput);
      } else {
        @$colorsFromInput = $this->body->color;
        if(isset($colorsFromInput) && is_array($colorsFromInput)) {
          list($r, $g, $b) = $colorsFromInput; 
          $colors = Color::FromRGB($r, $g, $b);
        }
      }
      $loop = 1;
      if (isset($this->body->loop)) {
        $loop = $this->body->loop;
      }
      for($i = 0; $i < $loop; ++$i) {
        if (isset($this->body->delay)) {
          if (isset($this->body->steps)) {
            $strand->$type($colors, $range, $this->body->delay, $this->body->steps);
          } elseif (isset($this->body->times)) {
            $strand->$type($colors, $range, $this->body->delay, $this->body->times);
          } else {
            $strand->$type($colors, $range, $this->body->delay);
          }
        } else {
          $strand->$type($colors, $range);
        }
      }
      $this->AddSuccessfulResponse('type', $type);
      $this->WriteResponseOut();
    }    
    if (!in_array($action, $basePropObjects)) {
      $this->ErrorOut(3);
    }
    $actionProperties = array_keys(get_object_vars($singleLight->$action));
    $strand = DI::GetStrand();
    foreach(array_keys(get_object_vars($this->body)) as $propertyName) {
      foreach($lights as &$led) {
        if (in_array($propertyName, $actionProperties)) {
          if ($this->Castable($led->$action->$propertyName, $this->body->$propertyName)) {
            $led->$action->$propertyName = $this->Cast($led->$action->$propertyName, $this->body->$propertyName);
            $this->AddSuccessfulResponse($propertyName, $this->body->$propertyName);
          } else {
            $this->AddErrorResponse(7, $propertyName);
          }
        } else {
          $this->AddErrorResponse(6, $propertyName);
        }
      }
    }
    $strand->Render();
    $this->WriteResponseOut();
  }

  private function ProcessGroups() {
  }

  private function ProcessConfig() {
  }

  private function ProcessScenes() {
  }

  private function ProcessRules() {
  }

  private function ProcessSensors() {
  }

  private function ProcessResouceLinks() {
  }

  private function GetLights(): array {
    return DI::GetStrand()->GetLeds();
  }

  private function GetGroups(): array {
    return [];
  }

  private function GetConfig(): array {
    return [];
  }

  private function GetScenes(): array {
    return [];
  }

  private function GetRules(): array {
    return [];
  }

  private function GetSensors(): array {
    return [];
  }

  private function GetResouceLinks(): array {
    return [];
  }

  private function ErrorOut(int $errorCode, string $part = '') {
    $this->AddErrorResponse($errorCode, $part);
    $this->WriteResponseOut();
  }

  private function WriteResponseOut() {
    echo json_encode($this->response);
    exit(1);
  }

  private function AddSuccessfulResponse(string $key, $value) {
    $this->response[] = (object)['success'=>(object)[$key=>$value]];
  }

  private function AddErrorResponse(int $errorCode, string $part = '') {
     $error = ['type'=>$errorCode, 'address'=>'/' . implode('/', array_slice($this->parts, 2))];
     if (strlen($part) > 0) {
       $error['address'] .= '/' . $part;
     }
    switch($errorCode) {
      case 1:
        $error['description'] = 'unauthorized user';
        break;
      case 3:
        $error['description'] = 'resource, ' . $error['address'] . ', not available' . $error['address'];
        break;
      case 4:
        $error['description'] = 'method, ' . $this->method . ', not available for resource, ' . $error['address'];
        break;
      case 6:
        $error['description'] = 'parameter, ' . $part . ', not available';
        break;
      case 7:
        $error['description'] = 'invalid value for paramater, ' . $part;
        break;
      default:
        $error['description'] = $part;
    }
    array_unshift($this->response, (object)[ 'error' => $error ]);
  }

  private function GetRange(): array {
    if (count($this->parts) <= 3) {
      return [];
    }
    $rangeAsString = $this->parts[3];
    if (is_numeric($rangeAsString)) {
      return [$rangeAsString + 0];
    }
    $rangeParts = explode(',', $rangeAsString);
    $range = [];
    foreach($rangeParts as $rangePart) {
      $miniRange = explode('-', $rangePart);
      if(count($miniRange) > 1) {
        list($min, $max) = $miniRange;
      } else {
        $min = $miniRange[0];
        $max = $min;
      }
      $range = array_merge($range, range($min, $max));
    }
    return $range;
  }

  private function Castable($oldValue, $newValue) :bool {
    if (gettype($oldValue) == gettype($newValue)) {
      return TRUE;
    }
    if (is_object($oldValue) || is_array($oldValue) || is_object($newValue) || is_array($newValue)) {
      return FALSE;
    }
    if (is_string($oldValue)) {
      return TRUE;
    }
    if (is_float($oldValue) && is_numeric($newValue)) {
      return TRUE;
    }
    return FALSE;
  } 

  private function Cast($oldValue, $newValue) {
    if (gettype($oldValue) == gettype($newValue)) {
      return $newValue;
    }
    if (is_string($oldValue)) {
      return (string)$newValue;
    }
    if (is_float($oldValue) && is_numeric($newValue)) {
      return (float)($newValue + 0);
    }
  }
}
