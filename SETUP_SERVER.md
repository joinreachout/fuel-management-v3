# Server Setup Commands

Execute these commands on the server via SSH:

```bash
# Navigate to project directory
cd ~/fuel.kittykat.tech/rev3

# Initialize git repository
git init

# Add remote repository
git remote add origin https://github.com/joinreachout/fuel-management-v3.git

# Fetch all branches
git fetch origin

# Checkout main branch
git checkout -b main origin/main

# Verify files are there
ls -la

# Check frontend files
ls -la frontend/dist/

# Done! Now git pull will work
git pull origin main
```

After this, you can use the deploy script or simply run `git pull` anytime.
