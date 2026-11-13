# xFee

<img src="xfee_theme/screenshot.jpg" style="max-width:600px; margin-left: 2rem; float:right;">

A system for handling volunteer shifts and thank-you goodies.

Use-case:

- You sign-up for a shift
- You finish the shift and receive acorns for it
- If you have enough acorns, you can pick a goodie and receive a pick-up code
- With that code you can pick-up your goodie with the help of an admin

## Features

- Shift sign-up with countdown
- Goodie shop with pick-up functions
- Minimal data collection & self cleaning
- Cancel & undo functions
- Extensive admin tools
- Integration with Authentik

## Installation

You can simply copy the theme in your clean WordPress installation and activate it. But you need to install Parsedown manually: xfee_theme/libs/parsedown/Parsedown.php. The Authentik integration will not be included.

But it is advised to use a setup with docker.

Build docker:

```
./docker/build-prod.sh
```

Development:

```
./docker/build.sh
```

For details about deployment and reverse proxy integration check the [Deployment Guide](docs/deployment.md).
For details about Authentik integration check the [Handover Documentation](docs/handover.md).

Finish the WordPress installation and activate the theme. xFee creates all the necessary pages and preferences 🧚

## TODOs

- Debug and error handling (use global debug option)
- Clean-up css
- Refactor JS (Mayve move to modules, like countdown to shift)
- Shrink WP (Remove media-upload, plugins, ...)
- Cancel goodie option
- Allow admins to finish a shift
- Access hardening
- Editor shift overview, remove assignment select from single shift

## Documentation

- [API Reference](docs/api.md)
- [Handover Documentation](docs/handover.md)
- [Deployment Guide](docs/deployment.md)
