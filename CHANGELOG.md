# Change Log
All notable changes to this project will be documented in this file
using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.
This project adheres to [Semantic Versioning](http://semver.org/).

## [0.1.3] 2016-05-17

### Added
- Add `Asserts::assertDefinitionSchema` to assert values against definition.

## [0.1.2] 2016-05-16

### Fixed
- Fixed normalizing headers with scalar value in HeadersConstraint.

## [0.1.1] 2016-05-15

### Added
- Implemented TestCase trait to add the assertions the PSR-7 objects match specification.
- Implemented `JsonSchemaConstraint` that asserts that the object matches the expected JSON Schema.
- Implemented `HeadersConstraint` that asserts that the headers list matches schema.
- Implemented `MethodsAllowedConstraint` that asserts that the HTTP method is allowed.
- Implemented `ContentTypeConstraint` that asserts that the content-type is allowed.
- Implemented `UriConstraint` that asserts that the URI.
- Implemented the OpenAPI Specification representation.
