<?php

namespace Websyspro\DynamicSql\Shareds;

use Exception;

class EvaluateFromString
{
	private static array $precedenceMap = [
		"||" => 1,
		"&&" => 2,
		"==" => 3, "!=" => 3, "===" => 3, "!==" => 3,
		 "<" => 4,  ">" => 4,  "<=" => 4, ">=" => 4,
		 "+" => 5,  "-" => 5,
		 "*" => 6,  "/" => 6,   "%" => 6
	];

  public static function execute(
		mixed $code
	): mixed {
		if(preg_match('/^\s*\w+\s*\(/', $code) === 0){
			return $code;
		}

    $tokens = (
			EvaluateFromString::normalizeTokens(
				array_slice(
					token_get_all(
						"<?php {$code}"
					), 1
				)
			)
		);
    
		$i = 0;
    return (
			EvaluateFromString::evaluate(
				EvaluateFromString::parseExpression(
					$tokens, $i
				)
			)
		);
  }

	private static function normalizeTokens(
		array $tokens
	): array {
		$combined = [];
		$i = 0;

		while($i < count($tokens)){
			$token = $tokens[$i];

			$next = $tokens[$i + 1] ?? null;
			$next2 = $tokens[$i + 2] ?? null;

			if($token === "=" && $next === "=" && $next2 === "="){
				$combined[] = "===";
				$i += 3;
				continue;
			}

			if($token === "!" && $next === "=" && $next2 === "="){
				$combined[] = "!==";
				$i += 3;
				continue;
			}

			if($token === "=" && $next === "="){
				$combined[] = "==";
				$i += 2;
				continue;
			}

			if($token === "!" && $next === "="){
				$combined[] = "!=";
				$i += 2;
				continue;
			}

			if($token === "<" && $next === "="){
				$combined[] = "<=";
				$i += 2;
				continue;
			}

			if($token === ">" && $next === "="){
				$combined[] = ">=";
				$i += 2;
				continue;
			}

			if($token === "&" && $next === "&"){
				$combined[] = "&&";
				$i += 2;
				continue;
			}

			if($token === "|" && $next === "|"){
				$combined[] = "||";
				$i += 2;
				continue;
			}

			$combined[] = $token;
			$i++;
		}

		return $combined;
	}


  private static function skipWhitespace(
		&$tokens, &$i
	): void {
    while($i < count($tokens)){
      if(is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE){
        $i++;
      } else {
        break;
      }
    }
  }

  private static function getPrecedence(
		string $precedenceIndex
	): int {
    return (
			EvaluateFromString::$precedenceMap[
				$precedenceIndex
			] ?? -1
		);
  }

  private static function parseExpression(
		array &$tokens,
		int &$i,
		int $minPrecedence = 0
	): mixed {
    $left = EvaluateFromString::parsePrimary(
			$tokens, $i
		);

    while($i < count($tokens)){
			$token = $tokens[$i];
			$op = is_array($token) ? $token[1] : $token;
			$op = trim($op);

			$precedence = EvaluateFromString::getPrecedence($op);
			if($precedence < $minPrecedence){
				break;
			}

			$i++;
			$right = (
				EvaluateFromString::parseExpression(
					$tokens, $i, $precedence + 1
				)
			);

			$left = [
				'op' => $op,
				'left' => $left,
				'right' => $right,
			];
    }

    return $left;
  }

  private static function parsePrimary(
		array &$tokens, 
		int &$i
	) {
		$token = $tokens[$i];

		if (is_array($token) && $token[0] === T_FN) {
			$i++;
			EvaluateFromString::skipWhitespace(
				$tokens, $i
			);

			$params = [];
			if ($tokens[$i] === '(') {
				$i++;
				while ($i < count($tokens)) {
					if (is_array($tokens[$i]) && $tokens[$i][0] === T_VARIABLE) {
						$params[] = ltrim($tokens[$i][1], '$');
						$i++;
					} elseif ($tokens[$i] === ',') {
						$i++;
					} elseif ($tokens[$i] === ')') {
						$i++;
						break;
					} else {
						$i++;
					}
				}
			}

			EvaluateFromString::skipWhitespace($tokens, $i);
			if ($tokens[$i] === '=>') {
				$i++;
				$body = EvaluateFromString::parseExpression($tokens, $i);
				return ['fn' => ['params' => $params, 'body' => $body]];
			}
		}

		if (is_array($token) && $token[0] === T_ARRAY) {
			$i++;
			EvaluateFromString::skipWhitespace($tokens, $i);
			if ($tokens[$i] === '(') {
				$i++;
				$items = EvaluateFromString::parseArrayItems($tokens, $i);
				return ['array' => $items];
			}
		}

		if ($token === '[') {
			$i++;
			$items = EvaluateFromString::parseArrayItems($tokens, $i, ']');
			return ['array' => $items];
		}

		if (is_array($token)) {
			switch ($token[0]) {
				case T_LNUMBER:
				case T_DNUMBER:
					$i++;
					return $token[1] + 0;

				case T_CONSTANT_ENCAPSED_STRING:
					$i++;
					return trim($token[1], "'\"");

				case T_VARIABLE:
					$i++;
					return ['var' => ltrim($token[1], '$')];

				case T_STRING:
					$i++;
					EvaluateFromString::skipWhitespace($tokens, $i);
					if ($tokens[$i] === '(') {
							$i++;
							$args = EvaluateFromString::parseArguments($tokens, $i);
							return ['name' => $token[1], 'args' => $args];
					}
					return $token[1];

				default:
					$i++;
					return null;
				}
		}

		if ($token === '(') {
			$i++;
			$expr = EvaluateFromString::parseExpression($tokens, $i);
			if ($tokens[$i] === ')') {
					$i++;
			}
			return $expr;
		}

		$i++;
		return null;
  }

  private static function parseArguments(&$tokens, &$i) {
		$args = [];
		while ($i < count($tokens)) {
			if ($tokens[$i] === ')') {
					$i++;
					break;
			}
			$args[] = EvaluateFromString::parseExpression($tokens, $i);
			if ($tokens[$i] === ',') {
					$i++;
			}
		}
		return $args;
  }

  private static function parseArrayItems(
		array &$tokens, 
		int &$i,
		string $end = ')'
	): array {
		$items = [];
		while( $i < count($tokens)){
			if ($tokens[$i] === $end) {
				$i++;
				break;
			}
			
			$items[] = EvaluateFromString::parseExpression(
				$tokens, $i
			);

			if ($tokens[$i] === ',') {
				$i++;
			}
		}
		return $items;
  }

  private static function evaluate(
		mixed $node,
		array $scope = []
	): mixed {
		if (is_numeric($node) || is_string($node)) {
			return $node;
		}

		if (is_array($node)) {
			if (isset($node['var'])) {
				return $scope[$node['var']] ?? null;
			}

			if (isset($node['op'])) {
				$left = EvaluateFromString::evaluate($node['left'], $scope);
				$right = EvaluateFromString::evaluate($node['right'], $scope);
				return match( $node[ "op" ]){
					'==' => $left == $right,
					'!=' => $left != $right,
					'===' => $left === $right,
					'!==' => $left !== $right,
					'>'  => $left > $right,
					'<'  => $left < $right,
					'>=' => $left >= $right,
					'<=' => $left <= $right,
					'+' => $left + $right,
					'-' => $left - $right,
					'*' => $left * $right,
					'/' => $left / $right,
					'%' => $left % $right,
					'&&' => $left && $right,
					'||' => $left || $right,

					default => throw new Exception(
						"Operador desconhecido: " . $node['op']
					)
				};
			}

			if (isset($node['array'])) {
				return array_map(fn($item) => EvaluateFromString::evaluate($item, $scope), $node['array']);
			}

			if (isset($node['name']) && isset($node['args'])) {
				$fn = $node['name'];
				$args = array_map(fn($arg) => EvaluateFromString::evaluate($arg, $scope), $node['args']);
				if( function_exists( $fn )) {
						return $fn(...$args);
				}

				throw new Exception(
					"Função '{$fn}' não existe"
				);
			}

			if( isset($node['fn']) ){
				return function (...$args) use ($node) {
					$newScope = [];
					foreach ($node['fn']['params'] as $i => $param) {
							$newScope[$param] = $args[$i] ?? null;
					}
					return EvaluateFromString::evaluate($node['fn']['body'], $newScope);
				};
			}
		}

		return null;
  }
}