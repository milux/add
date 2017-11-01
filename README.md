# ADD
An Annotation-Driven Dispatcher for PHP

This library offers an easy to use, simple dispatcher that calls methods via class names and method names obtained from PATH_INFO.

Furthermore, ADD calls the dispatched methods with parameters that are extracted from POST variables, GET variables, and additional elements of the PATH_INFO.
The description of the corresponding data sources is extracted from special annotations in the methods' DocComment.