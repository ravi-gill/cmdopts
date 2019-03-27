<?php

// This script parses command line arguments for PHP scripts given in the following format:

// php script.php [<command1> ... <commandn>] [<opt1> [<value>] ... <optn> [<valuen>]]

// E.g.: php script.php some command -x alpha -Y beta -z "charlie delta"

// E.g.: php script.php some command -x -y alpha

// E.g.: php script.php some command --option alpha -X -y beta

// Based on the rules below, it identifies and groups commands, short and long options, option values, and errors.

// A command is an argument that doesn't start with '-' or '--'.

// There can be more than one command.

// Commands must be placed at the

// Any commands found after one or more options have been entered are in error.

// An option is an argument that starts with either '-' or '--'.

// Options that start with '-' are short options. They must be a single alphabetic charater. E.g.: -h.

// Options that start with '--' are long options. They must be alphanumeric characters (i.e., more than one) and can include underscores. E.g.: --help.

// Options can have values.

// Option values must be placed after the option.

// If an option has a value, there must be at least a one character space between an option and its value.

echo PHP_EOL;

// This assertion throws an error if there are no commands or options in the command line beyond the script name.

assert( ( $argc > 1 ), new AssertionError( 'No commands or options given.' ) );

// Set up a queues for commands, options, and errors.

$cmdq = new SplQueue();

$optq = new SplQueue();

$valueq = new SplQueue();

$errorq = new SplQueue();

// Evaluate each argument.

foreach ( array_slice( $argv, 1 ) as $a ) {

    // The value queue should always have the same number of items as the option queue or 1 item less than the option queue.

    // If the option and value queues have the same number of items they are "balanced"; otherwise, they are "unbalanced".

    $diffcount = $optq->count() - $valueq->count();

    // This assertion throws an error if the difference between the number of options and values is neither 0 nor 1.

    assert(
        ( $diffcount === 0 || $diffcount === 1 ),
        new AssertionError( 'Difference between number of options and values is neither 0 nor 1.' )
    );

    // If the argument does not start with '-' it is either a command or option value.

    if ( preg_match( '/^(?!\-)/', $a ) ) {

        if ( $optq->count() === 0 ) {

            // If no options have been parsed treat this argument as a command.

            $cmdq->enqueue( $a );

        } else if ( $diffcount === 1) {

            // If the option and value queues are not balanced treat this argument as a value

            $valueq->enqueue( $a );

        } else {

            // The option and value queues are balanced so treat this argument as an error.

            $errorq->enqueue( $a );

        }

    // If the argument starts with '-' or '--' treat it as an option.

    } else if (
        preg_match( '/^\-{1}[a-zA-Z]{1}$/', $a ) ||
        preg_match( '/^\-{2}[a-zA-Z0-9_]{2,}$/', $a )
    ) {

        if ( $diffcount === 0 ) {

            // If the option and value queues are balanced add this argument to the option queue.

            $optq->enqueue( $a );

        } else if ( $diffcount == 1 ) {

            // If the option and value queues are unbalanced add an empty string to the value queue.

            $valueq->enqueue( '' );

            // Then, if this option has not already been entered add it to the option queue.

            $optq->enqueue( $a );

        }

    // If this argument does not meet any of above criteria add it to the error queue.

    } else {

        $errorq->enqueue( $a );

    }

}

// If the option and value queues are unbalanced add an empty string to the value queue.

if ( $optq->count() > $valueq->count() ) {

    $valueq->enqueue( '' );

}

// This assertion throws an error if the number of options and values are not equal.

assert(
    ( $optq->count() === $valueq->count() ),
    new AssertionError( 'Number of options and values are not equal.' )
);

// Transfer the commands, options, values and errors to an array.

$parsed = array();

$parsed['COMMANDS'] = array();

$cmdq->rewind();

while ( $cmdq->valid() ) {

    array_push( $parsed['COMMANDS'], $cmdq->current() );

    $cmdq->next();
}

$parsed['OPTIONS'] = array();

$optq->rewind();

$valueq->rewind();

while ( $optq->valid() && $valueq->valid() ) {

    $parsed['OPTIONS'][$optq->current()] = $valueq->current();

    $optq->next();

    $valueq->next();
}

$parsed['ERRORS'] = array();

$errorq->rewind();

while ( $errorq->valid() ) {

    array_push( $parsed['ERRORS'], $errorq->current() );

    $errorq->next();
}

print_r( $parsed ); echo PHP_EOL;

echo PHP_EOL;

exit( 0 );

?>
