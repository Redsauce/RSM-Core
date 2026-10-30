## ADDED Requirements

### Requirement: Select response storage by result field count
The existing XML responders SHALL attempt file-backed serialization when the configured threshold is positive and their result field count is strictly greater than that threshold (2000 by default), and SHALL otherwise serialize in memory. `RSReturnQueryResults()` SHALL use row count multiplied by field count. `RSReturnArrayQueryResults()` SHALL estimate the count as total rows multiplied by the rounded mean field count of the first ten rows, or all rows when fewer than ten. Its estimator SHALL return zero for non-array input and empty arrays. The count calculations retain the source-derived baseline behavior. The boundary scenarios below assume the default threshold.

#### Scenario: Database result above the boundary
- **WHEN** a database result contains 201 rows and 10 fields per row
- **THEN** the responder attempts temporary-file serialization because 2010 exceeds 2000

#### Scenario: Boundary remains in memory
- **WHEN** a database result contains 200 rows and 10 fields per row
- **THEN** the responder serializes in memory because 2000 does not exceed 2000

#### Scenario: Array estimation uses the first ten rows
- **WHEN** an array has 300 rows and its first ten rows each contain 7 fields
- **THEN** its estimated count is 2100 and the responder attempts file serialization

#### Scenario: Short array uses rounded mean
- **WHEN** an array has two rows containing respectively 2 and 3 fields
- **THEN** its estimated count is 2 multiplied by round(2.5), which is 6

#### Scenario: Non-array input does not trigger disk through the estimator
- **WHEN** `_predictNumberOfFields()` receives a `SplFixedArray` or an empty array
- **THEN** it returns zero

### Requirement: Serialize and deliver file-backed XML
When file serialization is selected and temporary-file creation and opening succeed, each responder SHALL write the XML response incrementally into a uniquely named `RSR` temporary file using `$RStempPath` as the requested directory. For a nonempty response file it SHALL send the file contents, with `Content-Length` equal to the transmitted file size. The XML SHALL contain the `RSRecordset`, `rows`, `row`, and named `column` structure, with values in CDATA and occurrences of `]]>` replaced by `$cstCDATAseparator` on the disk path.

#### Scenario: Successful uncompressed file response
- **WHEN** disk serialization succeeds and uncompressed output is allowed and requested
- **THEN** the responder sends the XML file with content type `text/xml` and the file size as content length

### Requirement: Preserve existing compression selection
The responders SHALL use compression unless uncompressed output is requested through `$cstRSsendUncompressed` or `$compressed` is false, and `$RSallowUncompressed` allows that choice. For file responses, successful gzip compression SHALL produce the transmitted `.gz` file with content type `application/x-gzip`; unsuccessful gzip compression SHALL leave the XML file as the transmitted response. Memory compression SHALL use the existing `checkCompressionVersion()` prefix behavior. This baseline does not require the disk path to add that prefix.

#### Scenario: Compression succeeds on disk
- **WHEN** a file response requires compression and `gzCompressFile()` succeeds
- **THEN** the uncompressed temporary file is removed and the gzip file is sent with its own byte length

#### Scenario: Compression fails on disk
- **WHEN** a file response requires compression and `gzCompressFile()` returns false
- **THEN** the original XML file is sent with content type `text/xml`

### Requirement: Fall back before a usable file response exists
When no nonempty temporary response file exists, the responders SHALL construct the XML response in memory using the available input. This requirement describes existing fallback selection and does not guarantee recovery from partially written files or already consumed database rows.

#### Scenario: Temporary file cannot be created
- **WHEN** temporary-file creation fails before serialization consumes rows
- **THEN** the responder builds and sends its response in memory

### Requirement: Clean up response files after delivery
Immediately after sending a file response, the responders SHALL call `removeTmpFile()` and delete the transmitted temporary file regardless of its age. There SHALL be no 86400-second waiting period for that response file. When gzip compression succeeds, the uncompressed temporary file SHALL also be deleted immediately after compression, before sending the gzip file. Normal completion SHALL retain the existing triggered-event checks, memory diagnostics, and request termination.

As a separate housekeeping operation, when the cleanup target exists, `removeTmpFile()` SHALL also remove other matching `RSR*` files whose creation time is more than 86400 seconds old in that directory. This age condition applies only to other leftover files, not to either temporary file belonging to the current response. Cleanup SHALL NOT indiscriminately delete recent files belonging to concurrent requests.

#### Scenario: Compressed response cleanup
- **WHEN** a temporary gzip response has been sent successfully
- **THEN** cleanup immediately removes that gzip response file even if it was created less than one second ago
- **AND** the uncompressed temporary file has already been deleted after compression

#### Scenario: Uncompressed response cleanup has no waiting period
- **WHEN** a newly created temporary XML response has been sent successfully without compression
- **THEN** cleanup immediately deletes that response file regardless of its creation time

#### Scenario: Other recent response files are preserved
- **WHEN** cleanup runs and the directory contains a recent `RSR*` file belonging to another request
- **THEN** the other recent file is preserved while the current response file is immediately deleted

### Requirement: Configure a shared field threshold
Both XML responders SHALL read a shared server setting `$RSresponseFileFieldThreshold` containing a non-negative PHP integer (without quotes). An absent or invalid setting SHALL resolve to 2000. The responders SHALL preserve the strict greater-than comparison and their existing field-count calculations. Request parameters SHALL NOT override this setting.

#### Scenario: Custom threshold
- **WHEN** the server threshold is 500 and either responder calculates 501 fields
- **THEN** that responder attempts file serialization

#### Scenario: Custom threshold boundary
- **WHEN** the server threshold is 500 and either responder calculates exactly 500 fields
- **THEN** that responder serializes in memory

#### Scenario: Missing or invalid configuration
- **WHEN** the server threshold is absent, negative, fractional, boolean, or nonnumeric
- **THEN** both responders use the default threshold of 2000

#### Scenario: Zero threshold
- **WHEN** the server threshold is zero
- **THEN** both XML responders serialize entirely in memory for any field count, including results above 2000 fields
- **AND** item queries bypass both their streaming and hydrated-array temporary-file paths even when file results were requested

### Requirement: Clean up request-owned files on abnormal termination
Every response temporary file SHALL be registered for shutdown cleanup immediately after creation, before serialization. A gzip destination SHALL be registered before compression starts. Shutdown cleanup SHALL close tracked streams or release tracked writers before attempting deletion, and SHALL use absolute paths so a changed working directory does not prevent deletion. The cleanup registry SHALL retain a small memory reserve released at shutdown to support cleanup after memory exhaustion. Cleanup SHALL only target files owned by the current request.

#### Scenario: Output aborts before normal cleanup
- **WHEN** PHP terminates a request during output before reaching its normal cleanup statements
- **THEN** the registered shutdown handler attempts immediate removal of all response files still owned by that request

#### Scenario: Fatal error or PHP timeout
- **WHEN** generation or compression terminates with an error, uncaught exception, memory exhaustion, or a PHP execution timeout that runs shutdown handlers
- **THEN** shutdown cleanup closes tracked handles and attempts immediate removal of the request's XML and gzip files

### Requirement: Remove failed compression output
Compression SHALL detect input read failures, failed or zero-length writes, partial writes, and close failures. On unsuccessful compression it SHALL close its handles and immediately attempt to remove the gzip destination, including when an exception interrupts compression. The XML source SHALL remain available for existing fallback delivery. The gzip destination SHALL remain tracked until its first deletion attempt, whether after failure or delivery.

#### Scenario: Gzip destination exists but input cannot open
- **WHEN** compression creates its destination and then fails to open its input
- **THEN** it returns false and immediately attempts to delete the gzip destination

#### Scenario: Compression cannot complete a write
- **WHEN** gzip writing returns false or zero before the input is fully written
- **THEN** compression returns false and immediately attempts to delete its partial destination

### Requirement: Detect deletion failure without retries
A deletion operation SHALL attempt removal once. It SHALL return false and log the path if deletion fails. The file SHALL be unregistered after that attempt even on failure, so shutdown cleanup does not retry it. Files that have not yet had a deletion attempt SHALL still receive shutdown cleanup. No code SHALL claim successful deletion while the file still exists.

#### Scenario: Successful deletion
- **WHEN** the single deletion attempt succeeds
- **THEN** cleanup reports success and unregisters the deleted file

#### Scenario: Persistent deletion failure
- **WHEN** the single deletion attempt fails
- **THEN** cleanup reports false, logs the failed path, and unregisters the file without retrying at shutdown

### Requirement: State cleanup operating limits
Immediate deletion guarantees SHALL be conditional on PHP executing cleanup and the filesystem allowing deletion. Forced process termination, server failure, and permanently denied deletion SHALL NOT be described as covered by successful immediate cleanup. Deployment SHALL use an independently scheduled stale-file cleanup if recovery from forced termination is required; this repository change does not configure a server scheduler.

#### Scenario: Process cannot execute shutdown
- **WHEN** the process is forcibly killed without running PHP shutdown callbacks
- **THEN** immediate deletion is not guaranteed and the remaining file requires an independent stale-file cleanup
### Requirement: Register item XML files before generation
Both `RSstreamFilteredItemsPropertiesToXML()` and `RSfilteredItemsArrayToXML()` SHALL create their files through the shared request-owned temporary-file helper and register their XMLWriter release callback before opening or writing the file. If opening fails they SHALL attempt immediate deletion through the checked deletion helper. Cleanup SHALL not depend on reaching `RSReturnFileResults()`.

#### Scenario: Streaming generation fails before returning a filename
- **WHEN** the streaming item writer encounters a fatal error while generating XML
- **THEN** shutdown cleanup releases its writer and deletes its registered temporary file when deletion is permitted

#### Scenario: Array generation fails before returning a filename
- **WHEN** the hydrated-array item writer encounters a fatal error while generating XML
- **THEN** shutdown cleanup releases its writer and deletes its registered temporary file when deletion is permitted
### Requirement: Apply the configured limit to item query file paths
`getFilteredItemsIDs()` SHALL use the value resolved by `RSGetResponseFileFieldThreshold()` for both its streaming and hydrated-array file decisions, replacing the fixed 1000-row limit. It SHALL preserve row-count measurement for these decisions, while RSdatabase response helpers continue measuring fields. File output SHALL require permission through allowFileResults, a positive threshold, and a row count strictly greater than that threshold.

#### Scenario: Custom limit on either item query path
- **WHEN** the configured value is 500, file results are allowed, and the item query contains 501 rows
- **THEN** the applicable streaming or hydrated-array path selects file output

#### Scenario: Boundary stays in memory
- **WHEN** the configured value is 500 and the item query contains exactly 500 rows
- **THEN** the item query returns results in memory

#### Scenario: Former fixed limit no longer applies
- **WHEN** the configured value is 2000 and an item query contains 1001 rows
- **THEN** neither item query file path selects file output