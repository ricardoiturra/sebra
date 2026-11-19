---
type: community
members: 5
---

# Logging Configuration

**Members:** 5 nodes

## Members
- [[MonologHandlerNullHandler]] - code
- [[MonologHandlerStreamHandler]] - code
- [[MonologHandlerSyslogUdpHandler]] - code
- [[MonologProcessorPsrLogMessageProcessor]] - code
- [[logging.php]] - code - config/logging.php

## Live Query (requires Dataview plugin)

```dataview
TABLE source_file, type FROM #community/Logging_Configuration
SORT file.name ASC
```
