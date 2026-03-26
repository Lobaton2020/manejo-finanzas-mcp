<?php
chdir('/var/www/manejo-finanzas-mcp');
require 'vendor/autoload.php';
require 'src/MCP/Tools/BaseTool.php';
require 'src/MCP/Tools/SharedFund/SharedFundTool.php';
require 'src/Database/Connection.php';
use Tools\SharedFund\SharedFundTool;
 = new SharedFundTool(new \Database\Connection());
echo json_encode(->addContribution(200000, 4, 2026, 'andres'));
