Building a pre-case diagnostic workflow using Gemini to trigger Action1 scripts is a fantastic way to cut down on initial triage time. Since Gemini relies heavily on the context it is fed, the goal here is to use Command Prompt (`cmd.exe`) native tools to generate clean, text-rich outputs that clearly highlight system states, errors, or anomalies without the overhead or current execution issues of PowerShell.

Here is a comprehensive roster of high-value, CMD-native diagnostic scripts categorized by common MSP troubleshooting scenarios.

### 1. Network & Connectivity Diagnostics

Network issues are the most common ticket generators. These scripts assess local configurations, routing, and external connectivity.

| Script Name | Description | CMD Command | MSP Use Case |
| --- | --- | --- | --- |
| **Diag-NetConfigAll** | Dumps the full IP configuration, MAC addresses, DHCP status, and DNS servers. | `ipconfig /all` | Base networking tickets; verifying DHCP handshakes and DNS assignments. |
| **Diag-NetConnections** | Lists all active TCP/IP connections, listening ports, and associated Process IDs (PIDs). | `netstat -ano` | Identifying rogue processes consuming bandwidth or checking if a local service is actively listening on its port. |
| **Diag-PingTestExternal** | Tests basic internet connectivity and DNS resolution against a reliable external host. | `ping 8.8.8.8 -n 4 & ping google.com -n 4` | Differentiating between a local physical disconnection, a routing issue, or a DNS resolution failure. |
| **Diag-DNSCacheDump** | Outputs the local DNS resolver cache to see recently resolved (or poisoned/stale) records. | `ipconfig /displaydns` | Troubleshooting specific website or application connectivity issues caused by stale local DNS. |

### 2. System Health & Resource Triage

When a user reports "my computer is slow" or "an app keeps crashing," these scripts grab the immediate hardware and resource context.

| Script Name | Description | CMD Command | MSP Use Case |
| --- | --- | --- | --- |
| **Diag-DiskSpaceSystem** | Retrieves the total and free space for the primary OS drive (usually C:). | `fsutil volume diskfree C:` | Catching full drives which cause system-wide instability, failed updates, or application crashes. |
| **Diag-SystemUptime** | Checks when the system was last booted. | `net statistics workstation | find "Statistics since"` | Validating if the user *actually* rebooted their machine before submitting the ticket. |
| **Diag-HighUsageProcs** | Lists running processes with their memory usage, filtering out system idle tasks. | `tasklist /v /fi "STATUS eq RUNNING"` | Feeding Gemini the process list to identify anomalous memory hogs or unexpected background software. |
| **Diag-BatteryHealth** *(Laptops)* | Generates a quick HTML battery health report (best if your Laravel app can parse or display the output file). | `powercfg /batteryreport /output "C:\temp\batt.html"` | Diagnosing sudden shutdowns or "wont hold charge" complaints on remote mobile endpoints. |

### 3. Services, Updates & Application Baseline

These scripts establish what is running and what changed recently, which is vital for post-update issues or failed line-of-business (LOB) apps.

| Script Name | Description | CMD Command | MSP Use Case |
| --- | --- | --- | --- |
| **Diag-StoppedAutoServices** | Lists services that are configured to start automatically but are currently stopped. | `wmic service where "StartMode='Auto' and State='Stopped'" get Name, DisplayName` | Finding crashed RMM agents, stuck print spoolers, or failed database services. |
| **Diag-RecentUpdates** | Lists recently installed Windows Updates (Hotfixes) with installation dates. | `wmic qfe list brief /format:table` | Correlating "my system is broken today" tickets with a patch applied the night before. |
| **Diag-SystemInfoTriage** | Pulls OS version, build, install date, RAM, and domain architecture. | `systeminfo` | Giving Gemini the complete system hardware/OS baseline to cross-reference known bugs for that specific build. |

### 4. Event Logs & Security Context

Parsing event logs via CMD can be tricky without PowerShell, but `wevtutil` is a highly effective native tool for grabbing recent critical errors.

| Script Name | Description | CMD Command | MSP Use Case |
| --- | --- | --- | --- |
| **Diag-RecentCritEvents** | Pulls the last 10 Critical and Error events from the System event log over the last 24 hours. | `wevtutil qe System /q:"*[System[(Level=1 or Level=2) and TimeCreated[timediff(@SystemTime) <= 86400000]]]" /f:text /c:10` | Identifying silent hardware failures, driver crashes, or unexpected reboots. |
| **Diag-RecentAppEvents** | Pulls the last 10 Error events from the Application event log. | `wevtutil qe Application /q:"*[System[(Level=2) and TimeCreated[timediff(@SystemTime) <= 86400000]]]" /f:text /c:10` | Troubleshooting specific line-of-business application crashes or .NET framework errors. |
| **Diag-LoggedOnUser** | Shows who is currently logged onto the machine and their session state. | `query user` | Identifying if multiple users are logged in, or confirming the context of the user reporting the issue. |
| **Diag-LocalAdmins** | Lists all members of the local Administrators group. | `net localgroup administrators` | Security audits; checking if a user has the rights required to perform a failing action, or spotting unauthorized escalation. |


### 5. Domain, Authentication & Group Policy

For environments running Active Directory or Entra ID (Azure AD), authentication and policy application failures are frequent culprits for access issues.

| Script Name | Description | CMD Command | MSP Use Case |
| --- | --- | --- | --- |
| **Diag-DomainTrust** | Tests the secure channel between the workstation and the Domain Controller. | `nltest /sc_query:%userdomain%` | Diagnosing "The trust relationship between this workstation and the primary domain failed" errors. |
| **Diag-GPOStatus** | Generates a summary of applied Group Policy Objects (GPOs) and the user's security groups. | `gpresult /r` | Verifying if a deployed drive mapping, printer, or security policy is actually reaching the endpoint. |
| **Diag-LogonServer** | Outputs the specific Domain Controller that authenticated the user's current session. | `echo %logonserver%` | Identifying if a user is authenticating against an offline, remote, or out-of-sync Domain Controller. |

### 6. Storage Integrity & Backups

Drive failures and backup service issues often happen silently until a user notices missing files or severe system lag.

| Script Name | Description | CMD Command | MSP Use Case |
| --- | --- | --- | --- |
| **Diag-SMARTStatus** | Queries the physical drives for their self-reported S.M.A.R.T. health status. | `wmic diskdrive get model,status` | Giving Gemini an early warning of impending physical hard drive failure ("Pred Fail" vs "OK"). |
| **Diag-VSSShadows** | Lists existing Volume Shadow Copies. | `vssadmin list shadows` | Troubleshooting failed endpoint backups (like Veeam or Datto) which rely heavily on VSS. |
| **Diag-SharedDrives** | Lists all active mapped network drives for the current user session. | `net use` | Diagnosing "I can't access the Z: drive" complaints to see if the mapping exists or is currently in a "Disconnected" state. |

### 7. Printers & Peripherals

Printers are notoriously problematic. Grabbing the local printer state saves your technicians from having to ask the user to read off printer names.

| Script Name | Description | CMD Command | MSP Use Case |
| --- | --- | --- | --- |
| **Diag-PrinterList** | Lists installed printers, identifying the default printer and current status. | `wmic printer get name,default,status` | Quickly determining if a missing printer is a driver issue, an offline status, or simply the wrong default printer selected. |
| **Diag-PrintSpooler** | Checks the exact state of the Windows Print Spooler service. | `sc query spooler` | The first step in almost any printing ticket is checking if the spooler has crashed or is stuck in a "Stopping" state. |

### 8. Advanced Network Routing & Proxies

When `ping` works but specific applications or web portals fail, the issue often lies in deeper network configurations.

| Script Name | Description | CMD Command | MSP Use Case |
| --- | --- | --- | --- |
| **Diag-SystemProxy** | Displays the Windows system-level HTTP proxy configuration. | `netsh winhttp show proxy` | Finding lingering proxy settings from old security agents or malware that are blocking internet access. |
| **Diag-ARPTable** | Displays the ARP cache, mapping IP addresses to physical MAC addresses on the local network. | `arp -a` | Identifying IP conflicts (two devices with the same IP) or checking if the default gateway is reachable at the MAC layer. |
| **Diag-HostFile** | Outputs the contents of the Windows HOSTS file, ignoring commented lines. | `findstr /v "^#" %windir%\System32\drivers\etc\hosts` | Spotting unauthorized redirects or outdated static DNS overrides put in place by a previous vendor. |

### Tips for Optimizing CMD Outputs for Gemini

1. **Format for LLM Readability:** When passing these outputs from Action1 into your Laravel app, you might want to wrap the output in clear markdown tags before sending it to the Gemini prompt (e.g., `Here is the output for Disk Space: \n ``` \n [Action1 Output] \n ````).
2. **Combine Small Scripts:** To save API calls to Action1, you can combine highly related commands using `&`. For example, a single script called `Diag-QuickNetwork` could contain: `ipconfig /all & ping 8.8.8.8 -n 4 & ipconfig /displaydns`.
3. **WMIC Deprecation Note:** While `wmic` is deprecated in newer Windows 11 builds, it is still functional and highly valuable for Windows 10 and Server environments typical of MSPs. If you encounter environments where it is removed, you will need to rely more heavily on `fsutil`, `tasklist`, and `sc`.

### A Note on Timeout Configurations

For Action1 and Gemini integrations, be careful with commands that take a long time to execute (like `chkdsk` or `tracert`). The scripts above are specifically chosen because they execute and return data almost instantly, which is exactly what you want for a synchronous, AI-driven pre-case triage workflow.

Would you like to explore how to structure the JSON payload or the Action1 API request to efficiently trigger these scripts from your Laravel backend?