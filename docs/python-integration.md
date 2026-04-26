# Python Export Scripts

This document describes the standalone Python scripts for data export functionality in the Mobilis system.

## Overview

The Python scripts provide multi-format data export (CSV, Excel, PDF) for the Mobilis vehicle rental system. These are standalone scripts that connect directly to the database and are called by PHP export files.

## Script Location

- **Directory**: `python-scripts/`
- **Language**: Python 3.11+
- **Database**: Direct MySQL connection via pymysql

## Installation

### Prerequisites

- Python 3.11 or higher
- pip package manager
- MySQL database access

### Setup Steps

1. Navigate to the python-scripts directory:
```bash
cd python-scripts
```

2. Install dependencies:
```bash
pip install openpyxl reportlab pymysql
```

3. Configure database connection in `config.py`:
```python
DB_HOST='127.0.0.1'
DB_PORT=3306
DB_NAME='mobilis_db'
DB_USER='root'
DB_PASS=''
```

## Available Scripts

### export_bookings.py
Exports booking data in CSV, Excel, or PDF format.

Usage:
```bash
python export_bookings.py <search> <from_date> <to_date> <status> <format> <output_file>
```

Parameters:
- `search`: Search query string
- `from_date`: Filter from date (YYYY-MM-DD)
- `to_date`: Filter to date (YYYY-MM-DD)
- `status`: Filter by booking status
- `format`: `csv`, `xlsx`, or `pdf`
- `output_file`: Output file path

### export_customers.py
Exports customer data in CSV, Excel, or PDF format.

Usage:
```bash
python export_customers.py <format> <output_file>
```

Parameters:
- `format`: `csv`, `xlsx`, or `pdf`
- `output_file`: Output file path

### export_vehicles.py
Exports vehicle data in CSV, Excel, or PDF format.

Usage:
```bash
python export_vehicles.py <status> <category> <search> <format> <output_file>
```

Parameters:
- `status`: Filter by vehicle status
- `category`: Filter by vehicle category
- `search`: Search query string
- `format`: `csv`, `xlsx`, or `pdf`
- `output_file`: Output file path

### export_payments.py
Exports payment data in CSV, Excel, or PDF format.

Usage:
```bash
python export_payments.py <status> <from_date> <to_date> <search> <format> <output_file>
```

Parameters:
- `status`: Filter by payment status
- `from_date`: Filter from date (YYYY-MM-DD)
- `to_date`: Filter to date (YYYY-MM-DD)
- `search`: Search query string
- `format`: `csv`, `xlsx`, or `pdf`
- `output_file`: Output file path

## PHP Integration

The PHP export files call these Python scripts using the `exec()` function. Example from `bookings-export.php`:

```php
$pythonScript = __DIR__ . '/../../python-scripts/export_bookings.py';
$command = "python \"$pythonScript\" \"$search\" \"$fromDate\" \"$toDate\" \"$status\" \"$format\" \"$outputFile\"";
exec($command, $output, $returnCode);
```

If the Python script fails, the PHP files fall back to CSV generation using PHP.

## Libraries Used

- **pymysql**: MySQL database connection
- **openpyxl**: Excel (.xlsx) file generation
- **reportlab**: PDF generation

## Troubleshooting

### Script Not Running

1. Verify Python is installed: `python --version`
2. Check if dependencies are installed: `pip list`
3. Verify database connection settings in `config.py`

### Export Not Working

1. Verify all Python dependencies are installed
2. Check database connection credentials
3. Test script directly from command line
4. Check file permissions for output directory

### Database Connection Errors

1. Verify MySQL server is running
2. Check database credentials in `config.py`
3. Ensure database exists and is accessible
4. Check firewall settings if connecting remotely

## Benefits

1. **Multi-format Exports**: Professional Excel and PDF exports with styling
2. **Direct Database Access**: No need for API calls
3. **Simple Integration**: Called directly from PHP via exec()
4. **Standalone**: No separate service to manage
5. **Flexible**: Easy to modify and extend
