-- Loads the Client Satisfactory Survey (Extension Project) questionnaire from files/survey form.docx:
-- 4 categories as criteria and their 10 questions, rated 1 (Strongly Disagree) to 5 (Strongly Agree).
-- Replaces all existing criteria, questions and answers. Backup: database/backups/2026-09-29_before_survey.sql

SET NAMES utf8mb4;

SET @academic_id = (SELECT `id` FROM `academic_list` WHERE `year` = '2026-2027' AND `semester` = 1 LIMIT 1);

TRUNCATE TABLE `evaluation_answers`;
TRUNCATE TABLE `question_list`;
TRUNCATE TABLE `criteria_list`;

-- Relevance and Usefulness
INSERT INTO `criteria_list` (`criteria`, `order_by`) VALUES ('PAGKAKAANGKOP AT KAPAKINABANGAN (Relevance and Usefulness)', 0);
SET @c = LAST_INSERT_ID();
INSERT INTO `question_list` (`academic_id`, `question`, `order_by`, `criteria_id`) VALUES
(@academic_id, 'Ang extension project na isinagawa ng kolehiyo/unibersidad ay tumugon sa mga natukoy na pangangailangan. (The extension project conducted by the college/university addressed our identified needs.)', 0, @c),
(@academic_id, 'Ang extension project ng kolehiyo/unibersidad ay nakapagbigay ng kaalaman at kakayahan na nagagamit sa aming pang-araw-araw na gawain. (The extension project of the college/university provided knowledge and skills that are useful in our daily activities.)', 1, @c);

-- Quality of Delivery
INSERT INTO `criteria_list` (`criteria`, `order_by`) VALUES ('KALIDAD NG PAGPAPATUPAD (Quality of Delivery)', 1);
SET @c = LAST_INSERT_ID();
INSERT INTO `question_list` (`academic_id`, `question`, `order_by`, `criteria_id`) VALUES
(@academic_id, 'Ang extension project ng kolehiyo/unibersidad ay matagumpay na ipinatupad/ipinapatupad batay sa napagkasunduang mga layunin. (The extension project of the college/university was successfully implemented/is being implemented according to its agreed objectives.)', 2, @c),
(@academic_id, 'Ang mga aktibidad ng extension project ng kolehiyo/unibersidad ay naisagawa/isinasagawa ayon sa napagkasunduang iskedyul. (The activities of the college/university extension project were carried out/are being carried out according to the agreed schedule.)', 3, @c),
(@academic_id, 'Ang mga aktibidad ng extension project ng kolehiyo/unibersidad ay malinaw at madaling maunawaan. (The activities of the college/university extension project are clear and easy to understand.)', 4, @c),
(@academic_id, 'Ang mga tagapagpatupad ng extension project ng kolehiyo/unibersidad ay nakikinig at tumutugon sa aming mga mungkahi. (The implementers of the college/university extension project listen to and respond to our suggestions.)', 5, @c);

-- Benefits and Impact
INSERT INTO `criteria_list` (`criteria`, `order_by`) VALUES ('BENEPISYO AT EPEKTO (Benefits and Impact)', 2);
SET @c = LAST_INSERT_ID();
INSERT INTO `question_list` (`academic_id`, `question`, `order_by`, `criteria_id`) VALUES
(@academic_id, 'Ang extension project ng kolehiyo/unibersidad ay nakatulong sa pagpapaunlad ng aming buhay. (The college/university extension project has contributed to the improvement of our lives.)', 6, @c),
(@academic_id, 'Ang extension project ng kolehiyo/unibersidad ay walang naidulot na anumang negatibong epekto sa amin. (The extension project of the college/university has not caused any negative effects on us.)', 7, @c);

-- Overall Satisfaction (questions 9 and 10 reworded as statements to fit the agree scale)
INSERT INTO `criteria_list` (`criteria`, `order_by`) VALUES ('PANGKALAHATANG KASIYAHAN (Overall Satisfaction)', 3);
SET @c = LAST_INSERT_ID();
INSERT INTO `question_list` (`academic_id`, `question`, `order_by`, `criteria_id`) VALUES
(@academic_id, 'Ako ay nasisiyahan sa isinagawa/isinasagawang extension project ng kolehiyo/unibersidad. (I am satisfied with the extension project implemented/being implemented by the college/university.)', 8, @c),
(@academic_id, 'Batay sa aking karanasan, irerekomenda ko sa iba na sumali sa extension project na ito kung may pagkakataon. (Based on my experience, I would recommend others to join this extension project, if given the opportunity.)', 9, @c);
