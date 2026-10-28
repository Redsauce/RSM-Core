# Picture request validation

## Purpose

Describe optional dimension validation in API v2 picture requests, corrected by commit 5f13cd0.

## Requirements

### Requirement: Picture dimensions are optional
The picture GET endpoint SHALL require `ID` and `propertyID` while allowing `w` and `h` to be omitted independently. Missing dimensions SHALL NOT produce undefined-array-key warnings. Supplied dimensions SHALL retain the existing integer validation and resizing behavior.

#### Scenario: Both dimensions omitted
- **WHEN** an authorized caller requests a supported picture with `ID` and `propertyID` and no `w` or `h`
- **THEN** dimension validation SHALL succeed without undefined-array-key warnings
- **AND** the endpoint SHALL use the original image dimensions

#### Scenario: One dimension supplied
- **WHEN** a caller supplies only a valid `w` or only a valid `h`
- **THEN** the missing dimension SHALL NOT produce an undefined-array-key warning
- **AND** existing resizing and adjustment rules SHALL determine the result

#### Scenario: Invalid supplied dimension
- **WHEN** a supplied non-null dimension does not represent an integer according to the existing validator
- **THEN** the endpoint SHALL return the existing HTTP 400 validation response
