<?php

namespace Jeurboy\SwaggerToAwsAPIGateway;

class SwaggerToAwsAPIGateway {
    protected $json_input;
    protected $json_output;

    protected $aws_uri;

    public function __construct(array $input) {
        $this->json_input = $input;
        $this->json_output = [];
    }
    public function setAwsUri(string $aws_uri) {
        $this->aws_uri = $aws_uri;
    }

    public function getOutput() : array {
        $this->processInfo();
        $this->processPaths();

        return $this->json_output;
    }

    protected function processInfo() : bool {
        $this->json_output['swagger'] = $this->json_input['swagger'];
        $info_keys = ['title', 'description', 'version'];

        foreach( $info_keys as $info_key ) {
            if (!empty($this->json_input['info'][$info_key])) {
                $this->json_output['info'][$info_key] = $this->json_input['info'][$info_key];
            }
        }

        return true;
    }

    protected function processPaths() : bool {
        $return = [];

        foreach ($this->json_input['paths'] as $route => $path) {
            $return[$route] = $this->processPath($route , $path);
        }

        $this->json_output['paths'] = $return;
        return true;
    }

    protected function processPath(string $route , array $param) : array {
        $return = [];

        $amazon_json = [
            'responses' => [
                'default' => [ "statusCode" => "200" ],
            ],
            'uri' => 'TO_BE_REPLACED',
            'passthroughBehavior' => 'when_no_match',
            'connectionType' => 'VPC_LINK',
            'connectionId' => '${stageVariables.vpcLinkId}',
            'httpMethod' => 'TO_BE_REPLACED',
            'type' => 'http_proxy'
        ];

        foreach ($param as $method_action => $parameters) {
            $path_parameter = [];
            $amazon_json['httpMethod'] = strtoupper($method_action);
            $amazon_json['uri']        = $this->aws_uri.$route;


            $matches = [];
            if (preg_match_all('/{([^{]*)}/', $route, $matches)) {
                foreach($matches[1] as $match) {
                    $amazon_json['requestParameters']['integration.request.path.'.$match] = "method.request.path.".$match;
                }
            }


            foreach ($parameters as $parameter_name => $parameter_value) {
                $add_param = false;
                switch ($parameter_name) {
                    case 'parameters' :
                        $parameter_value = $this->processRequestsParam($parameter_value);

                        if (!empty($parameter_value)) $add_param = true;
                    break;
                    case 'responses' :
                        $parameter_value = $this->processRequestsResponses($parameter_value);

                        if (!empty($parameter_value)) $add_param = true;
                    break;
                    case 'security' : break;
                    default :
                        $add_param = true;
                    break;
                }

                if ($add_param == true) {
                    $path_parameter[$parameter_name] = $parameter_value;
                }
            }

            $path_parameter['x-amazon-apigateway-integration'] = $amazon_json;

            $return[$method_action] = $path_parameter;
        }

        return $return;
    }

    protected function processRequestsParam(array $parameter_values) : array {
        $return = [];
        foreach ($parameter_values as $parameter_value) {
            if ( $parameter_value['in'] == 'path') {
                $return[] = $parameter_value;
            }
        }
        return $return;
    }
    protected function processRequestsResponses(array $parameter_values) : array {
        foreach ( array_keys($parameter_values) as  $return_code ) {
            unset($parameter_values[$return_code]['schema']);
        }
        return $parameter_values;
    }
}