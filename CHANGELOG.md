# Change Log
All notable changes to this project will be documented in this file
using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.
This project adheres to [Semantic Versioning](http://semver.org/).

## [0.9.0] 2021-08-12

### Changed

- Updated minimum requirements for `php` to version `7.3`

## [0.8.0] 2019-06-16

### Changed

- Updated minimum requirements for `php` to version `7.2`
- Updated `phpunit/phunit` to version `8.2`

## [0.7.0] 2019-05-25

### Changed

- Use multi-byte functions (#34) 
- Use more strict code (comparisons, assertions, type-hints) (#35) 

## [0.6.0] 2018-12-18

### Changed

- Migrate library to OpenAPI v3.0 format 

## [0.5.0] 2018-12-18

### Changed

- Updated `justinrainbow/json-schema` to version `5.2`
- Updated `phpunit/phunit` to version `7.5` 

## [0.4.0] 2018-12-01

### Fixed

- Change type of statuses returned by getResponseCodes from string to int 

## [0.3.1] 2018-02-17

### Changed

- Add phpunit 6.0 fallback version

## [0.3.0] 2018-02-17

### Changed

- Upgrade minimum of PHP version up to 7.1
- Upgrade minimum of PHPUnit version up to 7.0

## [0.2.0] 2017-10-29

### Changed

- Upgrade minimum of PHP version up to 7.0
- Upgrade minimum of PHPUnit version up to 6.0

## [0.1.5] 2016-07-04

### Fixed
- Fix request parameters builder (#22).

## [0.1.4] 2016-07-02

### Fixed
- Fix detecting the incorrect URL in the `SchemaFactory` (#20).

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
