---
type: community
cohesion: 0.25
members: 8
---

# Email Verification Tests

**Cohesion:** 0.25 - loosely connected
**Members:** 8 nodes

## Members
- [[dot-test_email_can_be_verified()]] - code - tests/Feature/EmailVerificationTest.php
- [[dot-test_email_can_not_verified_with_invalid_hash()]] - code - tests/Feature/EmailVerificationTest.php
- [[dot-test_email_verification_screen_can_be_rendered()]] - code - tests/Feature/EmailVerificationTest.php
- [[EmailVerificationTest]] - code - tests/Feature/EmailVerificationTest.php
- [[EmailVerificationTest.php]] - code - tests/Feature/EmailVerificationTest.php
- [[IlluminateAuthEventsVerified]] - code
- [[IlluminateSupportFacadesEvent]] - code
- [[IlluminateSupportFacadesURL]] - code

## Live Query (requires Dataview plugin)

```dataview
TABLE source_file, type FROM #community/Email_Verification_Tests
SORT file.name ASC
```

## Connections to other communities
- 2 edges to [[_COMMUNITY_Jetstream Features and Tests]]
- 2 edges to [[_COMMUNITY_Team Creation Tests]]
- 1 edge to [[_COMMUNITY_Fortify User Actions]]
- 1 edge to [[_COMMUNITY_Password Reset Tests]]

## Top bridge nodes
- [[EmailVerificationTest.php]] - degree 8, connects to 4 communities
- [[EmailVerificationTest]] - degree 6, connects to 2 communities