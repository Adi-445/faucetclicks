-- server/interest_calculator.lua
-- This would run as a scheduled task on the server
local function calculate_daily_interest()
    local db = require('firebase_db')
    local users = db:get_collection('users')
    
    for _, user in ipairs(users) do
        if user.points >= 500 then  -- Minimum for crypto conversion
            local crypto_value = user.points / 1000 * 0.01
            local daily_interest = crypto_value * 0.005  -- 0.5% daily
            
            -- Convert interest back to points
            local interest_points = daily_interest * 100 / 0.01
            
            -- Update user points
            db:update_document('users', user.id, {
                points = user.points + interest_points
            })
            
            -- Log the interest accrual
            log_interest_earned(user.id, interest_points)
        end
    end
end

-- Run daily at midnight
schedule_task('0 0 * * *', calculate_daily_interest)
